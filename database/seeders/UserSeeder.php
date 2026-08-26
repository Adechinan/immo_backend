<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un admin
        User::create([
            'prenom' => 'Aboki',
            'nom' => 'GBETO',
            // Format des numéros béninois depuis la renumérotation de 2021 :
            // 10 chiffres, préfixe "01" suivi de l'ancien numéro à 8 chiffres
            // (ex. réseau MTN Bénin : 01 97 xx xx xx).
            'tel' => '0197001122',
            'email' => 'aboki@gmail.com',
            'password' => Hash::make('password'),
            'type' => 'admin',
        ]);

        // Créer des utilisateurs de test — noms et numéros représentatifs du
        // Bénin (Cotonou, Abomey-Calavi, Porto-Novo...).
        $users = [
            [
                'prenom' => 'Kokou',
                'nom' => 'Dossou',
                'tel' => '0197234567',
                'email' => 'kokou.dossou@email.bj',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
            [
                'prenom' => 'Rachidatou',
                'nom' => 'Alassane',
                'tel' => '0196345678',
                'email' => 'rachidatou.alassane@email.bj',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
            [
                'prenom' => 'Fabrice',
                'nom' => 'Houngbédji',
                'tel' => '0161456789',
                'email' => 'fabrice.houngbedji@email.bj',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
            [
                'prenom' => 'Falilatou',
                'nom' => 'Chabi',
                'tel' => '0162567890',
                'email' => 'falilatou.chabi@email.bj',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
            [
                'prenom' => 'Sègbédji',
                'nom' => 'Agbodjan',
                'tel' => '0190678901',
                'email' => 'segbedji.agbodjan@email.bj',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
