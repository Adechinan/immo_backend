<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Équipements courants sur le marché immobilier béninois (Cotonou,
        // Abomey-Calavi...) — "Groupe électrogène" et "Forage" remplacent des
        // équivalents peu pertinents localement (chauffage, cave) : le climat
        // tropical ne demande pas de chauffage, et la nappe phréatique proche
        // de la surface rend les caves rares, alors que délestages électriques
        // et coupures d'eau rendent groupe électrogène et forage très
        // recherchés dans les annonces.
        $options = [
            ['name' => 'Piscine'],
            ['name' => 'Parking'],
            ['name' => 'Jardin'],
            ['name' => 'Terrasse'],
            ['name' => 'Cour clôturée'],
            ['name' => 'Ascenseur'],
            ['name' => 'Climatisation'],
            ['name' => 'Groupe électrogène'],
            ['name' => 'Forage'],
            ['name' => 'Gardien'],
        ];

        DB::table('options')->insert($options);
    }
}
