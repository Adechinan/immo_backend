<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Demande;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $biens = Bien::where('statut', 'disponible')->get();
        $users = User::where('type', '!=', 'admin')->get();

        foreach ($biens as $bien) {
            // Créer 1-3 demandes par bien
            $nbDemandes = rand(1, 3);
            
            for ($i = 0; $i < $nbDemandes; $i++) {
                $user = $users->random();
                
                Demande::create([
                    'bien_id' => $bien->id,
                    'message_demandeur' => "Bonjour, je suis très intéressé(e) par votre bien \"{$bien->titre}\". Serait-il possible de le visiter ? Merci.",
                    'email_demandeur' => $user->email,
                    'contact_demandeur' => $user->tel,
                ]);
            }
        }
    }
}
