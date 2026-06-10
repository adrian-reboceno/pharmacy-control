<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class LaboratoryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear el permiso
        $permission = Permission::firstOrCreate([
            'name' => 'catalog.laboratories.manage',
            'guard_name' => 'api',
        ]);

        // 2. Asignar a super-admin
        $superAdmin = Role::where('name', 'super-admin')
            ->where('guard_name', 'api')
            ->firstOrFail();

        $superAdmin->givePermissionTo($permission);

        // 3. Asignar a branch-manager (tiene acceso a catalog completo)
        $branchManager = Role::where('name', 'branch-manager')
            ->where('guard_name', 'api')
            ->firstOrFail();

        $branchManager->givePermissionTo($permission);

        // 4. Limpiar caché
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✔ catalog.laboratories.manage creado y asignado.');
    }
}
