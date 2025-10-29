<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for users
        $userPermissions = [
            'create users',
            'show users',
            'update users',
            'delete users',
        ];

        foreach ($userPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create permissions for collaborators
        $collaboratorPermissions = [
            'create collaborators',
            'show collaborators',
            'update collaborators',
            'delete collaborators',
        ];

        foreach ($collaboratorPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create general permissions
        $generalPermissions = [
            'manage users',
            'manage collaborators',
            'import csv',
        ];

        foreach ($generalPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles for web and API
        $managerApiRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $collaboratorApiRole = Role::firstOrCreate(['name' => 'collaborator', 'guard_name' => 'api']);

        // Assign permissions to manager role (api)
        $managerApiRole->syncPermissions(
            Permission::where('guard_name', 'api')
                ->whereIn('name', [
                    'manage users',
                    'create users',
                    'show users',
                    'update users',
                    'delete users',
                    'manage collaborators',
                    'create collaborators',
                    'show collaborators',
                    'update collaborators',
                    'delete collaborators',
                    'import csv',
                    'export csv',
                ])
                ->get()
        );


        // Assign limited permissions to collaborator role (api)
        $collaboratorApiRole->syncPermissions(
            Permission::where('guard_name', 'api')
                ->whereIn('name', ['show collaborators'])
                ->get()
        );

        $this->command->info('✅ Roles and permissions created successfully for API guards!');
    }
}
