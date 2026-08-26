<?php

namespace Database\Seeders;

use App\Models\Achat;
use App\Models\Bien;
use App\Models\Location;
use App\Models\Mensualite;
use App\Models\Statut;
use App\Models\User;
use Illuminate\Database\Seeder;

class AchatLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('type', '!=', 'admin')->get();

        // Créer quelques achats sur des biens à vendre
        $biensAVendre = Bien::whereHas('statut', fn ($q) => $q->where('code', 'a_vendre'))->take(2)->get();
        foreach ($biensAVendre as $bien) {
            $user = $users->random();

            $achat = Achat::create([
                'user_id' => $user->id,
                'bien_id' => $bien->id,
                'dateAchat' => now()->subDays(rand(10, 60)),
            ]);

            // Marquer le bien comme vendu
            $bien->update(['statut_id' => Statut::where('code', 'vendu')->value('id')]);
        }

        // Créer quelques locations sur des biens à louer
        $biensALouer = Bien::whereHas('statut', fn ($q) => $q->where('code', 'a_louer'))->take(2)->get();
        foreach ($biensALouer as $bien) {
            $user = $users->random();

            $location = Location::create([
                'user_id' => $user->id,
                'bien_id' => $bien->id,
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
            $bien->update(['statut_id' => Statut::where('code', 'loue')->value('id')]);
        }
    }
}
