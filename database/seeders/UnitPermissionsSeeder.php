<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

final class UnitPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name'       => 'catalog.units.manage',
            'guard_name' => 'api',
        ]);

        $superAdmin = Role::where('name', 'super-admin')
            ->where('guard_name', 'api')
            ->firstOrFail();
        $superAdmin->givePermissionTo($permission);

        $branchManager = Role::where('name', 'branch-manager')
            ->where('guard_name', 'api')
            ->firstOrFail();
        $branchManager->givePermissionTo($permission);

        $auditor = Role::where('name', 'auditor')
            ->where('guard_name', 'api')
            ->firstOrFail();
        $auditor->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('✔ catalog.units.manage creado y asignado.');
    }
}
