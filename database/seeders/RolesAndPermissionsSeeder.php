<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->assignPermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedPermissions(): void
    {
        foreach ($this->permissionList() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }
    }

    private function seedRoles(): void
    {
        foreach ($this->roleDefinitions() as $roleName => $definition) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

            DB::table('role_metadata')->updateOrInsert(
                ['role_id' => $role->id],
                [
                    'display_name'    => $definition['display_name'],
                    'hierarchy_level' => $definition['hierarchy_level'],
                    'branch_scoped'   => $definition['branch_scoped'],
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
        }
    }

    private function assignPermissions(): void
    {
        foreach ($this->rolePermissions() as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->where('guard_name', 'api')->firstOrFail();

            if ($roleName === 'super-admin') {
                $role->syncPermissions(Permission::where('guard_name', 'api')->get());
                continue;
            }

            $perms = Permission::whereIn('name', $permissionNames)
                ->where('guard_name', 'api')
                ->get();

            $role->syncPermissions($perms);
        }
    }

    /** @return string[] */
    private function permissionList(): array
    {
        return [
            // auth (14)
            'auth.users.create', 'auth.users.view', 'auth.users.edit', 'auth.users.deactivate',
            'auth.users.unlock', 'auth.users.force-password',
            'auth.roles.view', 'auth.roles.assign', 'auth.roles.revoke',
            'auth.sessions.view', 'auth.sessions.revoke',
            'auth.audit.view', 'auth.audit.export',
            'auth.2fa.manage',
            // catalog (7)
            'catalog.products.create', 'catalog.products.view', 'catalog.products.edit',
            'catalog.products.deactivate', 'catalog.products.price',
            'catalog.categories.manage',
            'catalog.suppliers.manage',
            // inventory (7)
            'inventory.batch.create', 'inventory.batch.view', 'inventory.batch.adjust',
            'inventory.batch.transfer', 'inventory.batch.writeoff',
            'inventory.reports.view', 'inventory.alerts.view',
            // sales (7)
            'sales.pos.operate', 'sales.pos.discount',
            'sales.controlled.dispense',
            'sales.refund.process',
            'sales.cashregister.open', 'sales.cashregister.close',
            'sales.reports.view',
            // prescriptions (5)
            'prescriptions.create', 'prescriptions.view', 'prescriptions.validate',
            'prescriptions.dispense', 'prescriptions.reports.view',
            // purchasing (5)
            'purchasing.order.create', 'purchasing.order.view', 'purchasing.order.approve',
            'purchasing.order.receive', 'purchasing.suppliers.manage',
            // customers (4)
            'customers.create', 'customers.view', 'customers.edit', 'customers.history.view',
            // billing (3)
            'billing.cfdi.generate', 'billing.cfdi.cancel', 'billing.cfdi.view',
            // reports (4)
            'reports.dashboard.view', 'reports.sales.export',
            'reports.cofepris.export', 'reports.inventory.export',
            // branches (3)
            'branches.view', 'branches.manage', 'branches.transfer',
            // config (3)
            'config.system.view', 'config.system.edit', 'config.integrations.manage',
        ];
    }

    /** @return array<string, array{display_name: string, hierarchy_level: int, branch_scoped: bool}> */
    private function roleDefinitions(): array
    {
        return [
            'super-admin'    => ['display_name' => 'Super Administrador',  'hierarchy_level' => 10, 'branch_scoped' => false],
            'branch-manager' => ['display_name' => 'Gerente de Sucursal',  'hierarchy_level' => 8,  'branch_scoped' => true],
            'pharmacist'     => ['display_name' => 'Farmacéutico',         'hierarchy_level' => 6,  'branch_scoped' => true],
            'cashier'        => ['display_name' => 'Cajero',               'hierarchy_level' => 4,  'branch_scoped' => true],
            'purchasing'     => ['display_name' => 'Encargado de Compras', 'hierarchy_level' => 4,  'branch_scoped' => true],
            'auditor'        => ['display_name' => 'Auditor',              'hierarchy_level' => 2,  'branch_scoped' => false],
        ];
    }

    /** @return array<string, string[]> */
    private function rolePermissions(): array
    {
        return [
            // super-admin: todos los permisos — manejado con syncPermissions(all) en assignPermissions()
            'super-admin' => [],

            // branch-manager: todos excepto 4 permisos administrativos/globales
            'branch-manager' => array_values(array_diff($this->permissionList(), [
                'config.system.edit',
                'config.integrations.manage',
                'branches.manage',
                'auth.users.force-password',
            ])),

            'pharmacist' => [
                'catalog.products.view',
                'inventory.batch.create', 'inventory.batch.view', 'inventory.batch.adjust', 'inventory.batch.writeoff',
                'inventory.alerts.view',
                'sales.pos.operate', 'sales.pos.discount', 'sales.controlled.dispense',
                'sales.refund.process', 'sales.cashregister.open', 'sales.cashregister.close',
                'prescriptions.create', 'prescriptions.view', 'prescriptions.validate', 'prescriptions.dispense',
                'purchasing.order.receive',
                'customers.create', 'customers.view', 'customers.history.view',
                'billing.cfdi.generate',
            ],

            // cashier: SIN controlled.dispense ni prescriptions (SSoD parcial)
            'cashier' => [
                'catalog.products.view',
                'sales.pos.operate', 'sales.refund.process',
                'sales.cashregister.open', 'sales.cashregister.close',
                'customers.create', 'customers.view',
                'billing.cfdi.generate',
            ],

            // purchasing: SIN order.approve (SSoD — quien crea órdenes no las aprueba)
            'purchasing' => [
                'catalog.products.view', 'catalog.suppliers.manage',
                'inventory.batch.create', 'inventory.batch.view', 'inventory.alerts.view',
                'purchasing.order.create', 'purchasing.order.view', 'purchasing.order.receive',
                'purchasing.suppliers.manage',
            ],

            // auditor: solo lectura en todos los módulos
            'auditor' => [
                'auth.users.view', 'auth.audit.view', 'auth.audit.export',
                'catalog.products.view',
                'inventory.batch.view', 'inventory.reports.view', 'inventory.alerts.view',
                'sales.reports.view',
                'prescriptions.view', 'prescriptions.reports.view',
                'purchasing.order.view',
                'customers.view', 'customers.history.view',
                'billing.cfdi.view',
                'reports.dashboard.view', 'reports.sales.export',
                'reports.cofepris.export', 'reports.inventory.export',
                'branches.view',
                'config.system.view',
            ],
        ];
    }
}
