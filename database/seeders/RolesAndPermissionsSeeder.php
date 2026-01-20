<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Création des permissions (seulement si elles n'existent pas)
        $permissions = [
            'boards.view-any',
            'boards.view-own',
            'boards.create',
            'boards.update',
            'boards.delete',
            'columns.create',
            'columns.update',
            'columns.delete',
            'cards.create',
            'cards.update',
            'cards.delete',
            'cards.move',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Création du rôle Admin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        // Création du rôle Viewer
        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);
        $viewerRole->syncPermissions([
            'boards.view-own',
            'boards.create',
            'boards.update',
            'columns.create',
            'columns.update',
            'cards.create',
            'cards.update',
            'cards.delete',
            'cards.move',
        ]);
    }
}
