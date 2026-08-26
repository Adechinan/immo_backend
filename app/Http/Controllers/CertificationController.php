<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CertificationController extends Controller
{
    /**
     * Historique + statut courant des demandes de certification de
     * l'utilisateur connecté (écran "Certification", mobile et web).
     */
    public function moi(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'est_certifie' => $user->estCertifie(),
                'derniere'     => $this->format($user->derniereCertification()),
                'historique'   => $user->certifications()->latest()->get()->map(fn ($c) => $this->format($c)),
            ],
        ]);
    }

    /**
     * Soumission (ou re-soumission après refus) des deux documents requis.
     * Une demande déjà 'en_attente' bloque une nouvelle soumission — pas la
     * peine d'encombrer la file d'attente admin avec des doublons tant que
     * la précédente n'a pas été traitée.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->certifications()->where('statut', 'en_attente')->exists()) {
            return response()->json([
                'message' => 'Une demande de certification est déjà en cours de traitement.',
            ], 422);
        }

        $data = $request->validate([
            'piece_identite'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
            'registre_commerce' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        // Écriture directe via `move()` (PHP natif), comme
        // `BienController::storeTofs` — PAS le facade `Storage`, dont
        // `config/filesystems.php` n'est pas publié dans ce projet.
        // `storage_path()`, contrairement à `public_path()`, pointe HORS du
        // webroot public : ces documents ne sont jamais accessibles par une
        // URL directe, uniquement via self::document() (authentifié).
        $dir = storage_path('app/certifications/' . $user->id);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pieceFile = $request->file('piece_identite');
        $pieceFilename = uniqid('piece_', true) . '.' . $pieceFile->getClientOriginalExtension();
        $pieceFile->move($dir, $pieceFilename);
        $piecePath = $user->id . '/' . $pieceFilename;

        $registreFile = $request->file('registre_commerce');
        $registreFilename = uniqid('registre_', true) . '.' . $registreFile->getClientOriginalExtension();
        $registreFile->move($dir, $registreFilename);
        $registrePath = $user->id . '/' . $registreFilename;

        $certification = Certification::create([
            'user_id'                => $user->id,
            'piece_identite_path'    => $piecePath,
            'registre_commerce_path' => $registrePath,
            'statut'                 => 'en_attente',
        ]);

        return response()->json([
            'message' => 'Demande de certification envoyée. Elle sera examinée par un administrateur.',
            'data'    => $this->format($certification),
        ], 201);
    }

    /**
     * File d'attente admin — filtrable par statut (défaut : en_attente,
     * l'onglet le plus utile pour un admin qui arrive sur l'écran).
     */
    public function index(Request $request): JsonResponse
    {
        $statut = $request->query('statut', 'en_attente');

        $query = Certification::with(['user', 'traitant'])->latest();
        if ($statut !== 'tous') {
            $query->where('statut', $statut);
        }

        return response()->json([
            'data' => $query->get()->map(fn ($c) => $this->format($c, withUser: true)),
        ]);
    }

    public function approuver(Request $request, Certification $certification): JsonResponse
    {
        $certification->update([
            'statut'         => 'approuve',
            'commentaire_admin' => null,
            'traite_par'     => $request->user()->id,
            'traite_le'      => now(),
        ]);

        return response()->json([
            'message' => 'Compte certifié.',
            'data'    => $this->format($certification->fresh(), withUser: true),
        ]);
    }

    public function rejeter(Request $request, Certification $certification): JsonResponse
    {
        $data = $request->validate([
            'commentaire' => 'nullable|string|max:500',
        ]);

        $certification->update([
            'statut'            => 'rejete',
            'commentaire_admin' => $data['commentaire'] ?? null,
            'traite_par'        => $request->user()->id,
            'traite_le'         => now(),
        ]);

        return response()->json([
            'message' => 'Demande de certification refusée.',
            'data'    => $this->format($certification->fresh(), withUser: true),
        ]);
    }

    /**
     * Téléchargement authentifié d'une pièce jointe — seul le demandeur ou
     * un admin peut y accéder (jamais d'URL publique pour ces documents,
     * contrairement aux photos de biens : cf. commentaire de la migration).
     */
    public function document(Request $request, Certification $certification, string $type): BinaryFileResponse|JsonResponse
    {
        $user = $request->user();
        if ($user->id !== $certification->user_id && $user->type !== 'admin') {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $relative = match ($type) {
            'piece-identite'    => $certification->piece_identite_path,
            'registre-commerce' => $certification->registre_commerce_path,
            default             => null,
        };

        if ($relative === null) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        $fullPath = storage_path('app/certifications/' . $relative);
        if (! is_file($fullPath)) {
            return response()->json(['message' => 'Document introuvable.'], 404);
        }

        return response()->file($fullPath);
    }

    private function format(?Certification $c, bool $withUser = false): ?array
    {
        if ($c === null) {
            return null;
        }

        return [
            'id'                => $c->id,
            'statut'            => $c->statut,
            'commentaire_admin' => $c->commentaire_admin,
            'traite_le'         => $c->traite_le?->toIso8601String(),
            'created_at'        => $c->created_at?->toIso8601String(),
            'user' => $withUser && $c->relationLoaded('user') && $c->user ? [
                'id'     => $c->user->id,
                'nom'    => $c->user->nom,
                'prenom' => $c->user->prenom,
                'email'  => $c->user->email,
                'tel'    => $c->user->tel,
            ] : null,
        ];
    }
}
