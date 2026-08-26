<?php

namespace Database\Seeders;

use App\Models\Abonnement;
use App\Models\User;
use Illuminate\Database\Seeder;

class FedapayCompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Pour pouvoir tester tout de suite le paiement de loyer/achat sans
     * passer par les écrans "Compte FedaPay" et "Mon abonnement" : donne à
     * chaque propriétaire de test (tous les users non-admin créés par
     * UserSeeder, puisque BienSeeder leur assigne des biens au hasard) les
     * clés FedaPay SANDBOX de la plateforme (config('services.fedapay'),
     * lues depuis .env — les mêmes que celles déjà utilisées pour le widget
     * de paiement) et un abonnement actif de 30 jours.
     *
     * En sandbox, réutiliser les clés de la plateforme comme "clé
     * personnelle" de chaque propriétaire n'a pas d'incidence — un compte
     * sandbox FedaPay n'encaisse rien de réel. En production, chaque
     * propriétaire doit impérativement renseigner ses propres clés
     * (`pk_live_...`/`sk_live_...`) via l'écran "Compte FedaPay" : ce seeder
     * n'est là que pour le développement/la démo.
     */
    public function run(): void
    {
        $publicKey = config('services.fedapay.public_key');
        $secretKey = config('services.fedapay.secret_key');
        $montant   = (int) config('services.abonnement.montant_mensuel');

        if (empty($publicKey) || empty($secretKey)) {
            return;
        }

        User::where('type', 'user')->get()->each(function (User $user) use ($publicKey, $secretKey, $montant) {
            $user->update([
                'fedapay_public_key' => $publicKey,
                'fedapay_secret_key' => $secretKey,
            ]);

            if (! $user->hasAbonnementActif()) {
                Abonnement::create([
                    'user_id'                => $user->id,
                    'montant'                => $montant,
                    'date_debut'             => now()->toDateString(),
                    'date_fin'               => now()->addDays(30)->toDateString(),
                    'statut'                 => 'actif',
                    'fedapay_transaction_id' => 'seed-sandbox',
                ]);
            }
        });
    }
}
