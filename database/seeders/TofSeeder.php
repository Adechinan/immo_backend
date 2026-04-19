<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Tof;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TofSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $biens = Bien::all();
        
        foreach ($biens as $bien) {
            // Créer 3-5 photos par bien
            $nbPhotos = rand(3, 5);
            
            for ($i = 1; $i <= $nbPhotos; $i++) {
                Tof::create([
                    'bien_id' => $bien->id,
                    'src' => "https://picsum.photos/seed/bien{$bien->id}_{$i}/800/600.jpg",
                ]);
            }
        }
    }
}
