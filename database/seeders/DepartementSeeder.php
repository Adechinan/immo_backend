<?php

namespace Database\Seeders;

use App\Models\Departement;
use Illuminate\Database\Seeder;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Les 12 départements du Bénin.
        $departements = [
            'Alibori', 'Atacora', 'Atlantique', 'Borgou', 'Collines', 'Couffo',
            'Donga', 'Littoral', 'Mono', 'Ouémé', 'Plateau', 'Zou',
        ];

        foreach ($departements as $nom) {
            Departement::updateOrCreate(['nom' => $nom]);
        }
    }
}
