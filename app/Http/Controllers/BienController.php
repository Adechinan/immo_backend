<?php

namespace App\Http\Controllers;

use App\Http\Requests\BienRequest;
use App\Http\Resources\BienResource;
use App\Models\Achat;
use App\Models\Bien;
use App\Models\Location;
use App\Models\Statut;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BienController extends Controller
{
    public function __construct(private FirebaseNotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Bien::with(['tofs', 'options', 'statut', 'user']);
        $this->applyFilters($query, $request->only([
            'ville', 'zone', 'titre', 'categorie', 'prix_min', 'prix_max',
            'statut', 'type', 'pieces', 'chambres', 'surface_min',
        ]));

        $this->applyProximity(
            $query,
            $request->filled('near_lat') ? (float) $request->near_lat : null,
            $request->filled('near_lng') ? (float) $request->near_lng : null,
            $request->filled('radius_km') ? (float) $request->radius_km : null,
        );

        $biens = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data'       => BienResource::collection($biens),
            'pagination' => [
                'total'        => $biens->total(),
                'per_page'     => $biens->perPage(),
                'current_page' => $biens->currentPage(),
                'last_page'    => $biens->lastPage(),
            ],
        ]);
    }

    /**
     * Applique aux filtres de recherche standard (ceux de `index`, cf.
     * `?ville=&zone=&prix_max=...`) à partir d'un tableau normalisé plutôt
     * que directement de la requête HTTP — permet à `rechercheIA` de
     * réutiliser exactement la même logique de filtrage avec des valeurs
     * extraites par le modèle plutôt que saisies dans des champs de
     * formulaire, sans dupliquer/faire diverger les deux recherches.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['ville'])) {
            $query->ville($filters['ville']);
        }
        // Recherche libre (quartier, ville, adresse) — alimentée par le champ
        // "Localisation" du public ET par la zone extraite en langage naturel.
        if (!empty($filters['zone'])) {
            $zone = $filters['zone'];
            $query->where(fn ($q) => $q->where('ville', 'ilike', "%{$zone}%")
                ->orWhere('adresse', 'ilike', "%{$zone}%"));
        }
        if (!empty($filters['titre'])) {
            $query->where('titre', 'ilike', '%' . $filters['titre'] . '%');
        }
        if (!empty($filters['categorie'])) {
            $query->where('categorie', $filters['categorie']);
        }
        if (isset($filters['prix_min']) && $filters['prix_min'] !== '') {
            $query->prixMin((float) $filters['prix_min']);
        }
        if (isset($filters['prix_max']) && $filters['prix_max'] !== '') {
            $query->prixMax((float) $filters['prix_max']);
        }
        if (!empty($filters['statut'])) {
            $query->statutCode($filters['statut']);
        }
        if (!empty($filters['type'])) {
            if ($filters['type'] === 'vente') {
                $query->enVente();
            } elseif ($filters['type'] === 'location') {
                $query->enLocation();
            }
        }
        if (isset($filters['pieces']) && $filters['pieces'] !== '') {
            $query->where('pieces', '>=', $filters['pieces']);
        }
        if (isset($filters['chambres']) && $filters['chambres'] !== '') {
            $query->where('chambres', '>=', $filters['chambres']);
        }
        if (isset($filters['surface_min']) && $filters['surface_min'] !== '') {
            $query->where('surface', '>=', $filters['surface_min']);
        }
    }

    /**
     * Recherche par proximité ("biens près de moi") : quand une position est
     * fournie, on calcule la distance à vol d'oiseau (formule de Haversine,
     * en km) pour chaque bien géolocalisé et on trie du plus proche au plus
     * loin. La formule est répétée dans le WHERE (plutôt qu'un HAVING sur
     * l'alias du SELECT, peu portable sans GROUP BY) pour appliquer un rayon
     * optionnel. Partagée entre `index` (paramètres `near_lat`/`near_lng` du
     * client) et `rechercheIA` (position envoyée par le client + intention
     * "près de moi" détectée dans le texte libre) pour ne pas dupliquer la
     * requête SQL entre les deux points d'entrée.
     */
    private function applyProximity(Builder $query, ?float $lat, ?float $lng, ?float $radiusKm): void
    {
        if ($lat === null || $lng === null) {
            return;
        }

        $distanceExpr = '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))';

        $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("biens.*, {$distanceExpr} as distance_km", [$lat, $lng, $lat]);

        if ($radiusKm !== null) {
            $query->whereRaw("{$distanceExpr} <= ?", [$lat, $lng, $lat, $radiusKm]);
        }

        $query->orderBy('distance_km');
    }

    /**
     * Recherche en langage naturel ("un logement à Womey, budget 20 000") —
     * traduit le texte libre en les mêmes filtres que `index` via l'API
     * Claude (tool use, sortie JSON forcée), puis relance la recherche
     * normale avec ces filtres. Le récapitulatif renvoyé (`filtres_compris`)
     * permet au client d'afficher "Recherche : ≤ 20 000 FCFA, Womey" et de
     * laisser l'utilisateur corriger si le modèle s'est trompé.
     */
    public function rechercheIA(Request $request): JsonResponse
    {
        $data = $request->validate([
            'texte'     => 'required|string|max:500',
            // Position envoyée systématiquement (best-effort) par le client
            // quand la géolocalisation est disponible — n'est utilisée pour
            // trier par distance que si `pres_de_moi` est détecté dans le
            // texte (cf. criteresSchema), pas à chaque recherche.
            'near_lat'  => 'nullable|numeric|between:-90,90',
            'near_lng'  => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0',
        ]);

        $filters = $this->extraireFiltres($data['texte']);
        if ($filters === null) {
            return response()->json(['message' => "Impossible d'interpréter cette recherche. Réessayez avec d'autres mots."], 422);
        }

        $presDeMoi = !empty($filters['pres_de_moi']);

        $query = Bien::with(['tofs', 'options', 'statut', 'user']);
        $this->applyFilters($query, $filters);
        if ($presDeMoi) {
            $this->applyProximity(
                $query,
                isset($data['near_lat']) ? (float) $data['near_lat'] : null,
                isset($data['near_lng']) ? (float) $data['near_lng'] : null,
                isset($data['radius_km']) ? (float) $data['radius_km'] : null,
            );
        }
        $biens = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data'            => BienResource::collection($biens),
            'filtres_compris' => $filters,
            'pagination'      => [
                'total'        => $biens->total(),
                'per_page'     => $biens->perPage(),
                'current_page' => $biens->currentPage(),
                'last_page'    => $biens->lastPage(),
            ],
        ]);
    }

    /**
     * Schéma JSON des critères extraits — partagé entre Anthropic (qui
     * l'enveloppe dans un "tool") et Gemini (qui l'utilise tel quel comme
     * `response_schema`), pour ne pas maintenir deux définitions divergentes.
     */
    private function criteresSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'zone' => [
                    'type'        => 'string',
                    'description' => "Ville, quartier et/ou point de repère mentionné, recopié tel quel (ex: 'Womey', 'Calavi Womey', 'marché de Dantokpa'). Inclut aussi les lieux mentionnés via 'près de'/'pas loin de'/'à côté de' + un nom de lieu (ex: \"pas loin de Womey\" → zone='Womey') — à distinguer de 'pres_de_moi', qui concerne la position GPS de l'utilisateur, pas un lieu nommé. Absent si aucune localisation n'est mentionnée.",
                ],
                'type' => [
                    'type' => 'string',
                    'enum' => ['location', 'vente'],
                    'description' => "'location' si l'utilisateur cherche à louer, 'vente' si acheter. Absent si ambigu.",
                ],
                'categorie' => [
                    'type' => 'string',
                    'enum' => ['appartement', 'maison', 'penthouse', 'villa', 'studio'],
                ],
                'prix_min'    => ['type' => 'number', 'description' => 'Budget minimum en FCFA, si mentionné.'],
                'prix_max'    => ['type' => 'number', 'description' => 'Budget maximum en FCFA, si mentionné.'],
                'pieces'      => ['type' => 'integer', 'description' => 'Nombre de pièces minimum souhaité.'],
                'chambres'    => ['type' => 'integer', 'description' => 'Nombre de chambres minimum souhaité.'],
                'surface_min' => ['type' => 'number', 'description' => 'Surface minimum en m², si mentionnée.'],
                'pres_de_moi' => [
                    'type'        => 'boolean',
                    'description' => "true UNIQUEMENT si l'utilisateur veut des biens proches de sa PROPRE position GPS actuelle (ex: \"près de moi\", \"autour de moi\", \"pas loin d'ici\", \"dans les environs\" sans lieu précisé). Mets false/omets si l'utilisateur mentionne plutôt un lieu précis (ville, quartier, point de repère) — ex: \"pas loin de Womey\", \"proche du marché de Dantokpa\", \"près de Cotonou\" : ce sont des localités, à mettre dans 'zone', PAS des demandes de proximité GPS.",
                ],
            ],
            'required' => [],
        ];
    }

    /**
     * Traduit le texte libre en filtres en essayant chaque fournisseur IA
     * configuré (`services.ai_search.providers`, dans l'ordre — par défaut
     * gemini → groq → mistral → anthropic) jusqu'à ce que l'un d'eux
     * réponde. Ce relais entre plusieurs API gratuites permet de continuer à
     * servir la recherche même si l'une d'elles a atteint son quota gratuit
     * du jour, sans dépendre d'un seul fournisseur ni forcer un compte payant.
     */
    private function extraireFiltres(string $texte): ?array
    {
        $providers = config('services.ai_search.providers', ['gemini', 'groq', 'mistral', 'anthropic']);

        foreach ($providers as $provider) {
            $apiKey = config("services.{$provider}.api_key");
            if (empty($apiKey)) {
                continue;
            }

            $filters = match ($provider) {
                'gemini'    => $this->extraireFiltresGemini($texte, $apiKey),
                'groq'      => $this->extraireFiltresGroq($texte, $apiKey),
                'mistral'   => $this->extraireFiltresMistral($texte, $apiKey),
                'anthropic' => $this->extraireFiltresAnthropic($texte, $apiKey),
                default     => null,
            };

            if ($filters !== null) {
                return $filters;
            }
        }

        Log::warning('Recherche IA impossible : aucun fournisseur configuré/disponible parmi ' . implode(', ', $providers) . ' (clés API manquantes ou tous en échec).');
        return null;
    }

    /**
     * Décrit le schéma attendu en une instruction texte, pour les
     * fournisseurs utilisés en mode "JSON object" plutôt qu'en JSON Schema
     * forcé (Groq, Mistral) — ces API garantissent une sortie JSON valide
     * mais pas sa conformité stricte à un schéma, d'où le rappel explicite
     * des champs attendus dans le prompt système.
     */
    private function instructionExtraction(): string
    {
        $schemaJson = json_encode($this->criteresSchema(), JSON_UNESCAPED_UNICODE);

        return "Tu extrais les critères de recherche de bien immobilier depuis le texte de l'utilisateur. "
            . "Réponds UNIQUEMENT avec un objet JSON valide respectant ce schéma (n'inclus que les champs "
            . "explicitement mentionnés ou clairement déductibles, omets les autres) : {$schemaJson}";
    }

    /**
     * Appelle l'API Messages d'Anthropic avec un outil ("tool use") dont le
     * schéma force une sortie JSON structurée — plus fiable que de parser un
     * texte libre renvoyé par le modèle. Retourne `null` si l'appel échoue
     * ou si la réponse ne contient pas le bloc `tool_use` attendu (modèle
     * indisponible, timeout, etc.) : au client de retomber sur les filtres
     * classiques dans ce cas plutôt que de planter la recherche.
     */
    private function extraireFiltresAnthropic(string $texte, string $apiKey): ?array
    {
        $schema = [
            'name'         => 'criteres_recherche_immobiliere',
            'description'  => 'Critères de recherche de bien immobilier extraits du texte de l\'utilisateur.',
            'input_schema' => $this->criteresSchema(),
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
                'model'      => config('services.anthropic.model'),
                'max_tokens' => 1024,
                'tools'      => [$schema],
                'tool_choice' => ['type' => 'tool', 'name' => 'criteres_recherche_immobiliere'],
                'messages'   => [
                    ['role' => 'user', 'content' => $texte],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur appel API Anthropic (recherche IA) : ' . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::warning('Réponse Anthropic non réussie (recherche IA) : ' . $response->status() . ' ' . $response->body());
            return null;
        }

        $blocks = $response->json('content', []);
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'tool_use') {
                // Filtre les valeurs vides/nulles renvoyées par le modèle
                // (il inclut parfois une clé avec une valeur vide plutôt que
                // de l'omettre) avant de les passer à `applyFilters`.
                return array_filter(
                    $block['input'] ?? [],
                    fn ($v) => $v !== null && $v !== ''
                );
            }
        }

        return null;
    }

    /**
     * Appelle l'API Gemini (`generateContent`) avec une sortie JSON forcée
     * via `response_schema` — on utilise volontairement cet endpoint
     * "legacy" plutôt que la nouvelle API "Interactions" (GA depuis juin
     * 2026) : `generateContent` reste pleinement supporté par Google et son
     * enveloppe de réponse REST est stable et documentée, ce qui est plus
     * sûr pour un appel HTTP brut (sans SDK) que la nouvelle API dont la
     * forme exacte de réponse est moins bien documentée à ce jour.
     */
    private function extraireFiltresGemini(string $texte, string $apiKey): ?array
    {
        $model = config('services.gemini.model', 'gemini-3.7-flash');
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'content-type'   => 'application/json',
            ])->timeout(15)->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $texte]]],
                ],
                'systemInstruction' => [
                    'parts' => [[
                        'text' => "Extrais les critères de recherche de bien immobilier depuis le texte de l'utilisateur, selon le schéma JSON fourni. N'inclus que les champs explicitement mentionnés ou clairement déductibles.",
                    ]],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'response_schema'    => $this->criteresSchema(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur appel API Gemini (recherche IA) : ' . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::warning('Réponse Gemini non réussie (recherche IA) : ' . $response->status() . ' ' . $response->body());
            return null;
        }

        $texteJson = $response->json('candidates.0.content.parts.0.text');
        if (!is_string($texteJson) || $texteJson === '') {
            Log::warning('Réponse Gemini sans texte JSON exploitable (recherche IA) : ' . $response->body());
            return null;
        }

        $decoded = json_decode($texteJson, true);
        if (!is_array($decoded)) {
            Log::warning('JSON Gemini invalide (recherche IA) : ' . $texteJson);
            return null;
        }

        return array_filter($decoded, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Appelle l'API Groq (compatible OpenAI) en mode JSON object. Free tier
     * sans carte bancaire (~30 req/min, ~14 400 req/jour), inférence rapide :
     * bon relais si Gemini est indisponible ou à quota.
     */
    private function extraireFiltresGroq(string $texte, string $apiKey): ?array
    {
        return $this->extraireFiltresChatOpenAiCompatible(
            'https://api.groq.com/openai/v1/chat/completions',
            config('services.groq.model', 'llama-3.3-70b-versatile'),
            $apiKey,
            $texte,
            'Groq'
        );
    }

    /**
     * Appelle l'API Mistral "La Plateforme" (également compatible OpenAI)
     * en mode JSON object. Palier gratuit "Experiment" (1 req/s) : dernier
     * relais gratuit avant Anthropic (payant).
     */
    private function extraireFiltresMistral(string $texte, string $apiKey): ?array
    {
        return $this->extraireFiltresChatOpenAiCompatible(
            'https://api.mistral.ai/v1/chat/completions',
            config('services.mistral.model', 'mistral-small-latest'),
            $apiKey,
            $texte,
            'Mistral'
        );
    }

    /**
     * Logique partagée entre Groq et Mistral : toutes deux exposent une API
     * `chat/completions` compatible OpenAI (`Authorization: Bearer`,
     * `response_format: {type: json_object}`), seuls l'URL et le modèle
     * diffèrent — d'où cette factorisation plutôt que deux copies quasi
     * identiques.
     */
    private function extraireFiltresChatOpenAiCompatible(string $url, string $model, string $apiKey, string $texte, string $nomFournisseur): ?array
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->post($url, [
                    'model'           => $model,
                    'response_format' => ['type' => 'json_object'],
                    'messages'        => [
                        ['role' => 'system', 'content' => $this->instructionExtraction()],
                        ['role' => 'user', 'content' => $texte],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::error("Erreur appel API {$nomFournisseur} (recherche IA) : " . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            Log::warning("Réponse {$nomFournisseur} non réussie (recherche IA) : " . $response->status() . ' ' . $response->body());
            return null;
        }

        $contenu = $response->json('choices.0.message.content');
        if (!is_string($contenu) || $contenu === '') {
            Log::warning("Réponse {$nomFournisseur} sans contenu exploitable (recherche IA) : " . $response->body());
            return null;
        }

        $decoded = json_decode($contenu, true);
        if (!is_array($decoded)) {
            Log::warning("JSON {$nomFournisseur} invalide (recherche IA) : " . $contenu);
            return null;
        }

        return array_filter($decoded, fn ($v) => $v !== null && $v !== '');
    }

    public function store(BienRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        // Le statut initial dérive du type choisi (vente => à vendre, location => à louer)
        $typeBien = $data['type_bien'] ?? 'location';
        unset($data['type_bien'], $data['statut'], $data['tofs']);
        $data['statut_id'] = Statut::where('code', $typeBien === 'vente' ? 'a_vendre' : 'a_louer')->value('id');

        $bien = Bien::create($data);

        // Attacher les options si fournies
        if ($request->filled('options')) {
            $bien->options()->sync($request->options);
        }

        $this->storeTofs($request, $bien);

        return response()->json([
            'message' => 'Bien créé avec succès.',
            'data'    => new BienResource($bien->load(['tofs', 'options', 'statut'])),
        ], 201);
    }

    /**
     * Déplace les photos uploadées (`tofs[]`, multipart) vers public/uploads/biens
     * et crée les enregistrements `Tof` correspondants avec une URL absolue.
     *
     * On écrit directement dans public/ plutôt que de passer par le disque
     * `Storage::disk('public')` : ce dernier suppose un lien symbolique créé
     * via `php artisan storage:link`, qui n'a jamais été mis en place sur cet
     * environnement (aucun mécanisme de stockage de fichiers n'existait avant
     * cet ajout — cf. TofController::store, qui n'acceptait jusqu'ici qu'une
     * URL déjà hébergée ailleurs). Cette approche évite cette dépendance.
     *
     * L'URL est construite à partir de l'hôte de la requête entrante
     * (`getSchemeAndHttpHost`) plutôt que de `config('app.url')`, pour rester
     * correcte quel que soit l'hôte/IP utilisé pour joindre l'API (utile en
     * développement local, où l'app mobile joint le backend via l'IP LAN de
     * la machine plutôt que "localhost").
     */
    private function storeTofs(Request $request, Bien $bien): void
    {
        if (!$request->hasFile('tofs')) {
            return;
        }

        $dir = public_path('uploads/biens');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        foreach ($request->file('tofs') as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $filename = uniqid('bien_', true) . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $bien->tofs()->create([
                'src' => $request->getSchemeAndHttpHost() . '/uploads/biens/' . $filename,
            ]);
        }
    }

    public function show(Bien $bien): JsonResponse
    {
        $bien->load(['tofs', 'options', 'statut', 'user', 'achats.user', 'locations.user', 'demandes']);

        return response()->json(['data' => new BienResource($bien)]);
    }

    /**
     * Le propriétaire assigne un locataire ou un acheteur à son bien,
     * soit parmi les utilisateurs de l'app, soit en renseignant les coordonnées manuellement.
     */
    public function assignerOccupant(Request $request, Bien $bien): JsonResponse
    {
        if ($bien->user_id !== $request->user()->id) {
            abort(403, "Vous n'êtes pas autorisé à gérer ce bien.");
        }

        $data = $request->validate([
            'type'              => 'required|in:location,vente',
            'user_id'           => 'nullable|integer|exists:users,id',
            'nom_manuel'        => 'required_without:user_id|string|max:255',
            'email_manuel'      => 'nullable|email',
            'tel_manuel'        => 'nullable|string|max:50',
            'jour_encaissement' => 'required_if:type,location|nullable|integer|between:1,31',
        ]);

        $bien->loadMissing('statut');

        $occupant = [
            'bien_id'      => $bien->id,
            'user_id'      => $data['user_id']      ?? null,
            'nom_manuel'   => $data['nom_manuel']    ?? null,
            'email_manuel' => $data['email_manuel']  ?? null,
            'tel_manuel'   => $data['tel_manuel']    ?? null,
        ];

        if ($data['type'] === 'location') {
            if ($bien->statut->code !== 'a_louer') {
                return response()->json(['message' => "Ce bien n'est pas disponible à la location."], 422);
            }

            Location::create([
                ...$occupant,
                'dateLocation'      => now()->toDateString(),
                'jour_encaissement' => $data['jour_encaissement'],
            ]);
            $bien->update(['statut_id' => Statut::where('code', 'loue')->value('id')]);
        } else {
            if ($bien->statut->code !== 'a_vendre') {
                return response()->json(['message' => "Ce bien n'est pas disponible à la vente."], 422);
            }

            Achat::create([...$occupant, 'dateAchat' => now()->toDateString()]);
            $bien->update(['statut_id' => Statut::where('code', 'vendu')->value('id')]);
        }

        // Notifie l'utilisateur assigné, s'il s'agit d'un compte de l'app
        // (pas de notif possible pour un occupant saisi manuellement, sans
        // compte ni device token).
        if (! empty($data['user_id'])) {
            $occupantUser = User::find($data['user_id']);
            if ($occupantUser) {
                $this->notifications->sendToUser(
                    $occupantUser,
                    $data['type'] === 'location' ? 'Bien assigné' : 'Achat confirmé',
                    $data['type'] === 'location'
                        ? "Vous avez été assigné comme locataire de \"{$bien->titre}\"."
                        : "L'achat de \"{$bien->titre}\" vous a été assigné.",
                    ['type' => 'assignation', 'bien_id' => (string) $bien->id],
                );
            }
        }

        return response()->json([
            'message' => $data['type'] === 'location' ? 'Locataire assigné avec succès.' : 'Acheteur assigné avec succès.',
            'data'    => new BienResource($bien->fresh()->load(['tofs', 'options', 'statut', 'achats.user', 'locations.user'])),
        ]);
    }

    public function update(BienRequest $request, Bien $bien): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('type_bien', $data)) {
            unset($data['type_bien']);
        }
        if (! empty($data['statut'])) {
            $data['statut_id'] = Statut::where('code', $data['statut'])->value('id');
            unset($data['statut']);
        }

        $bien->update($data);

        if ($request->filled('options')) {
            $bien->options()->sync($request->options);
        }

        return response()->json([
            'message' => 'Bien mis à jour avec succès.',
            'data'    => new BienResource($bien->load(['tofs', 'options', 'statut'])),
        ]);
    }

    public function destroy(Bien $bien): JsonResponse
    {
        $bien->delete();

        return response()->json(['message' => 'Bien supprimé avec succès.'], 204);
    }

    /**
     * Clé publique FedaPay à utiliser pour payer ce bien (celle du
     * propriétaire, pas celle de la plateforme) + disponibilité du service —
     * consulté par l'écran de paiement du locataire/acheteur avant d'ouvrir
     * le checkout. `disponible` est false si le propriétaire n'a pas
     * d'abonnement actif ou n'a pas renseigné sa clé publique, cf.
     * `User::peutRecevoirPaiement`.
     */
    public function paiementInfo(Bien $bien): JsonResponse
    {
        $bien->loadMissing('user');
        $proprietaire = $bien->user;

        $disponible = $proprietaire !== null && $proprietaire->peutRecevoirPaiement();

        return response()->json([
            'data' => [
                'disponible'         => $disponible,
                'fedapay_public_key' => $disponible ? $proprietaire->fedapay_public_key : null,
            ],
        ]);
    }

    public function dernieresAnnonces(): JsonResponse
    {
        $biens = Bien::with(['tofs', 'options', 'statut', 'user'])
            ->disponible()
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return response()->json([
            'data' => $biens,
            'total' => $biens->count(),
        ]);
    }
}
