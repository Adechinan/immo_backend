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
            'prenom' => 'Admin',
            'nom' => 'System',
            'tel' => '0123456789',
            'email' => 'admin@immobilier.fr',
            'password' => Hash::make('password'),
            'type' => 'admin',
        ]);

        // Créer des utilisateurs de test
        $users = [
            [
                'prenom' => 'Jean',
                'nom' => 'Dupont',
                'tel' => '0601020304',
                'email' => 'jean.dupont@email.fr',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
            [
                'prenom' => 'Marie',
                'nom' => 'Martin',
                'tel' => '0605040302',
                'email' => 'marie.martin@email.fr',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
            [
                'prenom' => 'Pierre',
                'nom' => 'Durand',
                'tel' => '0607080910',
                'email' => 'pierre.durand@email.fr',
                'password' => Hash::make('password'),
                'type' => 'user',
            ],
             [
                'prenom' => 'Aboki',
                'nom' => 'GBETO',
                'tel' =>   '0190180786',
                'email' => 'aboki@gmail.com',
                'password' => Hash::make('password'),
                'type' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
