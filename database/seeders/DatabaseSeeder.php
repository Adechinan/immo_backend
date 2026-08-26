<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StatutSeeder::class,
            OptionSeeder::class,
            DepartementSeeder::class,
            VilleSeeder::class,
            ArrondissementSeeder::class,
            QuartierSeeder::class,
            UserSeeder::class,
            FedapayCompteSeeder::class,
            BienSeeder::class,
            TofSeeder::class,
            DemandeSeeder::class,
            AchatLocationSeeder::class,
            RolesAndPermissionsSeeder::class,
        ]);
    }
}
