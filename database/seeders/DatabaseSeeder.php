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
            OptionSeeder::class,
            UserSeeder::class,
            BienSeeder::class,
            TofSeeder::class,
            DemandeSeeder::class,
            AchatLocationSeeder::class,
            RolesAndPermissionsSeeder::class,
        ]);
    }
}
