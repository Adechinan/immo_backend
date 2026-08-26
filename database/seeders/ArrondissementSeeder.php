<?php

namespace Database\Seeders;

use App\Models\Arrondissement;
use App\Models\Ville;
use Illuminate\Database\Seeder;

class ArrondissementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Niveau intermédiaire optionnel entre ville et quartier. Limité pour
        // l'instant aux 13 arrondissements officiels de Cotonou (les seuls
        // dont le découpage est repris ici de façon fiable) — extensible plus
        // tard à d'autres communes (Porto-Novo, Parakou...) une fois leurs
        // arrondissements confirmés.
        $cotonouId = Ville::where('nom', 'Cotonou')->value('id');

        if ($cotonouId) {
            foreach (range(1, 13) as $numero) {
                Arrondissement::updateOrCreate(
                    ['nom' => "{$numero}" . ($numero === 1 ? 'er' : 'e') . ' Arrondissement', 'ville_id' => $cotonouId],
                    []
                );
            }
        }
    }
}
