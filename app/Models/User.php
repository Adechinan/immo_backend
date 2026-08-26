<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'prenom',
        'nom',
        'tel',
        'email',
        'password',
        'type',
        'google_id',
        'fedapay_public_key',
        'fedapay_secret_key',
        'statut',
        'statut_motif',
        'statut_updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // Jamais renvoyée au client — seule fedapay_public_key (par nature
        // publique, cf. commentaire de la migration) doit l'être.
        'fedapay_secret_key',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'fedapay_secret_key' => 'encrypted',
            'statut_updated_at'  => 'datetime',
        ];
    }

    /**
     * Seul un compte "actif" peut se connecter — vérifié par
     * AuthController::login et ::google avant l'émission du token Sanctum.
     */
    public function estActif(): bool
    {
        return $this->statut === 'actif';
    }

    // Relations
    public function achats()
    {
        return $this->hasMany(Achat::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function biensAchetes()
    {
        return $this->hasManyThrough(Bien::class, Achat::class, 'user_id', 'id', 'id', 'bien_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }

    /**
     * Dernier abonnement encore valide (statut actif et date_fin non
     * dépassée), ou null. Point d'entrée unique pour savoir si ce
     * propriétaire a accès à la réception de paiement.
     */
    public function abonnementActif(): ?Abonnement
    {
        return $this->abonnements()
            ->where('statut', 'actif')
            ->whereDate('date_fin', '>=', now()->toDateString())
            ->orderByDesc('date_fin')
            ->first();
    }

    public function hasAbonnementActif(): bool
    {
        return $this->abonnementActif() !== null;
    }

    /**
     * Accès à la réception de paiement de ses locataires/acheteurs :
     * suppose à la fois un abonnement LoggyImmo actif ET des clés FedaPay
     * personnelles renseignées (sinon les paiements n'ont nulle part où
     * aller). Utilisé par BienController::paiementInfo et
     * MensualiteController::store.
     */
    public function peutRecevoirPaiement(): bool
    {
        return $this->hasAbonnementActif() && ! empty($this->fedapay_public_key);
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class);
    }

    /**
     * Dernière demande de certification (quel que soit son statut) — pour
     * afficher l'état courant sur les écrans "Certification".
     */
    public function derniereCertification(): ?Certification
    {
        return $this->certifications()->latest()->first();
    }

    /**
     * Badge de confiance uniquement — n'ouvre l'accès à aucune fonctionnalité
     * (cf. décision produit : la certification est purement informative).
     */
    public function estCertifie(): bool
    {
        return $this->certifications()->where('statut', 'approuve')->exists();
    }
}
