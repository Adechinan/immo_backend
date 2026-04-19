<?php

namespace Database\Seeders;

use App\Models\Achat;
use App\Models\Bien;
use App\Models\BiensEnVente;
use App\Models\BiensEnLocation;
use App\Models\Location;
use App\Models\Mensualite;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AchatLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('type', '!=', 'admin')->get();
        
        // Créer quelques achats
        $biensEnVente = Bien::all()->take(2);
        foreach ($biensEnVente as $bien) {
            $user = $users->random();
            
            // Créer l'entrée BiensEnVente si elle n'existe pas
            $bienEnVente = $bien->bienEnVente ?? $bien->bienEnVente()->create();
            
            $achat = Achat::create([
                'user_id' => $user->id,
                'bien_en_vente_id' => $bienEnVente->id,
                'dateAchat' => now()->subDays(rand(10, 60)),
            ]);
            
            // Marquer le bien comme vendu
            $bien->update(['statut' => 'vendu']);
        }
        
        // Créer quelques locations
        $biensEnLocation = Bien::all()->take(2);
        foreach ($biensEnLocation as $bien) {
            $user = $users->random();
            
            // Créer l'entrée BiensEnLocation si elle n'existe pas
            $bienEnLocation = $bien->bienEnLocation ?? $bien->bienEnLocation()->create();
            
            $location = Location::create([
                'user_id' => $user->id,
                'bien_en_location_id' => $bienEnLocation->id,
                'dateLocation' => now()->subMonths(rand(1, 6)),
            ]);
            
            // Créer quelques mensualités payées
            for ($i = 1; $i <= 3; $i++) {
                Mensualite::create([
                    'location_id' => $location->id,
                    'datePaiement' => now()->subMonths($i),
                    'dateLoyer' => $bien->prix,
                ]);
            }
            
            // Marquer le bien comme loué
            $bien->update(['statut' => 'loue']);
        }
    }
}
