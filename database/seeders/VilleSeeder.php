<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Ville;
use Illuminate\Database\Seeder;

class VilleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Communes béninoises regroupées par département — sert à peupler le
        // select "ville" (publication d'annonce + filtres de recherche), à
        // la place d'un champ texte libre, avec le département en repère
        // pour un futur select en cascade département → ville. Liste
        // volontairement centrée sur les communes les plus significatives
        // pour l'immobilier (pas les 77 communes du pays).
        $parDepartement = [
            'Littoral'    => ['Cotonou'],
            'Atlantique'  => ['Abomey-Calavi', 'Ouidah', 'Allada', 'Tori-Bossito', 'Kpomassè', 'Zè', 'Toffo', 'Sô-Ava'],
            'Ouémé'       => ['Porto-Novo', 'Sèmè-Podji', 'Adjarra', 'Avrankou'],
            'Zou'         => ['Abomey', 'Bohicon'],
            'Mono'        => ['Lokossa', 'Comè', 'Grand-Popo'],
            'Couffo'      => ['Aplahoué', 'Dogbo'],
            'Borgou'      => ['Parakou', 'Nikki'],
            'Alibori'     => ['Kandi', 'Malanville', 'Banikoara'],
            'Atacora'     => ['Natitingou', 'Tanguiéta'],
            'Donga'       => ['Djougou'],
            'Plateau'     => ['Sakété', 'Pobè', 'Kétou'],
            'Collines'    => ['Savalou', 'Dassa-Zoumè'],
        ];

        foreach ($parDepartement as $departementNom => $villes) {
            $departementId = Departement::where('nom', $departementNom)->value('id');

            foreach ($villes as $nom) {
                Ville::updateOrCreate(['nom' => $nom], ['departement_id' => $departementId]);
            }
        }
    }
}
