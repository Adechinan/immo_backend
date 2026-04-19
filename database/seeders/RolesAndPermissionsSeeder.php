<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Permissions
    Permission::create(['name' => 'voir biens']);
    Permission::create(['name' => 'publier bien']);
    Permission::create(['name' => 'modifier bien']);
    Permission::create(['name' => 'supprimer bien']);
    Permission::create(['name' => 'gerer utilisateurs']);

    // Rôles
    Role::create(['name' => 'user'])
        ->givePermissionTo(['voir biens', 'publier bien', 'modifier bien', 'supprimer bien']);

    Role::create(['name' => 'admin'])
        ->givePermissionTo(Permission::all());
}
}
