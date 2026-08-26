<?php

namespace Database\Seeders;

use App\Models\Quartier;
use App\Models\Ville;
use Illuminate\Database\Seeder;

class QuartierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Quartiers regroupés par commune — repris notamment des adresses
        // utilisées dans BienSeeder. Volontairement limité aux communes où
        // l'on dispose de noms de quartiers fiables (les grandes villes) ;
        // les autres communes n'ont pour l'instant aucun quartier seedé, ce
        // qui reste sans incidence puisque ce niveau est optionnel dans le
        // formulaire. arrondissement_id est laissé à null : la correspondance
        // quartier ↔ arrondissement précise n'est pas encore fiabilisée.
        $parVille = [
            'Cotonou' => [
                'Akpakpa', 'Fidjrossè', 'Cadjèhoun', 'Sainte Rita', 'Zogbo', 'Agla',
                'Vêdoko', 'Missité', 'Ganhi', 'Jonquet', 'Gbégamey', 'Dantokpa', 'Haie Vive',
            ],
            'Abomey-Calavi' => [
                'Womey', 'Cocotomey', 'Godomey', 'Togba', 'Zogbadjè', 'Akassato',
            ],
            'Porto-Novo' => [
                'Ouando', 'Attakè', 'Djassin', 'Houinmè',
            ],
            'Sèmè-Podji' => [
                'Ekpè', 'Djèffa', 'Aholouyèmè',
            ],
            'Parakou' => [
                'Banikanni', 'Titirou', 'Zongo',
            ],
            'Ouidah' => [
                'Pahou',
            ],
        ];

        foreach ($parVille as $villeNom => $quartiers) {
            $villeId = Ville::where('nom', $villeNom)->value('id');
            if (! $villeId) {
                continue;
            }

            foreach ($quartiers as $nom) {
                Quartier::updateOrCreate(['nom' => $nom, 'ville_id' => $villeId], []);
            }
        }
    }
}
