<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ProductPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear permisos de productos
        $permissions = [
            'catalog.products.create',
            'catalog.products.view',
            'catalog.products.edit',
            'catalog.products.deactivate',
            'catalog.products.manage',  // permiso maestro que agrupa todos
        ];

        $created = [];
        foreach ($permissions as $name) {
            $created[$name] = Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'api',
            ]);
        }

        // 2. Asignar TODOS los permisos al super-admin
        $superAdmin = Role::where('name', 'super-admin')
            ->where('guard_name', 'api')
            ->firstOrFail();

        foreach ($created as $permission) {
            $superAdmin->givePermissionTo($permission);
        }

        // 3. Asignar a branch-manager (gestión completa del catálogo)
        $branchManager = Role::where('name', 'branch-manager')
            ->where('guard_name', 'api')
            ->firstOrFail();

        foreach ($created as $permission) {
            $branchManager->givePermissionTo($permission);
        }

        // 4. Limpiar caché
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✔ Permisos de catalog.products.* creados y asignados.');
    }
}