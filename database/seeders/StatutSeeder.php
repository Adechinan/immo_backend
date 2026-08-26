<?php

namespace Database\Seeders;

use App\Models\Statut;
use Illuminate\Database\Seeder;

class StatutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuts = [
            ['code' => 'a_vendre', 'libelle' => 'À vendre', 'couleur' => 'amber'],
            ['code' => 'a_louer',  'libelle' => 'À louer',  'couleur' => 'primary'],
            ['code' => 'vendu',    'libelle' => 'Vendu',    'couleur' => 'rose'],
            ['code' => 'loue',     'libelle' => 'Loué',     'couleur' => 'violet'],
            ['code' => 'reserve',  'libelle' => 'Réservé',  'couleur' => 'neutral'],
        ];

        foreach ($statuts as $statut) {
            Statut::updateOrCreate(['code' => $statut['code']], $statut);
        }
    }
}
