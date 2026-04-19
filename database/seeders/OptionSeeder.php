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
        $options = [
            ['name' => 'Piscine'],
            ['name' => 'Parking'],
            ['name' => 'Jardin'],
            ['name' => 'Terrasse'],
            ['name' => 'Cave'],
            ['name' => 'Ascenseur'],
            ['name' => 'Climatisation'],
            ['name' => 'Chauffage'],
            ['name' => 'Interphone'],
            ['name' => 'Gardien'],
        ];

        DB::table('options')->insert($options);
    }
}
