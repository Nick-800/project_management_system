<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'manage_projects',
            'manage_tasks',
            'manage_comments',
            'manage_tags',
            'view_reports',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'project_manager', 'guard_name' => 'web']);
        $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        $admin->givePermissionTo(Permission::all());

        $manager->givePermissionTo([
            'manage_projects',
            'manage_tasks',
            'manage_comments',
            'manage_tags',
            'view_reports',
        ]);

        $member->givePermissionTo([
            'manage_tasks',
            'manage_comments',
        ]);
    }
}
