<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoDExclusionsSeeder extends Seeder
{
    // UUID nil RFC 4122 — representa "el sistema"; nunca puede iniciar sesión
    private const SYSTEM_USER_ID = '00000000-0000-0000-0000-000000000000';

    public function run(): void
    {
        $this->ensureSystemUser();
        $this->seedExclusions();
    }

    private function ensureSystemUser(): void
    {
        DB::table('users')->insertOrIgnore([
            'id' => self::SYSTEM_USER_ID,
            'email' => 'system@pharmacontrol.internal',
            // Hash inválido deliberado — esta cuenta nunca debe autenticarse
            'password_hash' => '$2y$12$SYSTEM.ACCOUNT.DO.NOT.LOGIN.xxxxxxxxxxxxxxxxxxxxxxxx',
            'first_name' => 'System',
            'last_name' => 'PharmaControl',
            'status' => 'INACTIVE',
            'two_factor_enabled' => false,
            'must_change_password' => false,
            'failed_login_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedExclusions(): void
    {
        $roleIds = Role::whereIn('name', [
            'super-admin', 'branch-manager', 'pharmacist',
            'cashier', 'purchasing', 'auditor',
        ])->where('guard_name', 'api')->pluck('id', 'name');

        foreach ($this->exclusionList() as $exclusion) {
            $roleAId = $roleIds[$exclusion['role_a']] ?? null;
            $roleBId = $roleIds[$exclusion['role_b']] ?? null;

            if (! $roleAId || ! $roleBId) {
                continue;
            }

            DB::table('role_exclusions')->updateOrInsert(
                ['role_a_id' => $roleAId, 'role_b_id' => $roleBId],
                [
                    'level' => $exclusion['level'],
                    'reason' => $exclusion['reason'],
                    'created_by' => self::SYSTEM_USER_ID,
                    'created_at' => now(),
                ]
            );
        }
    }

    /** @return array<int, array{role_a: string, role_b: string, level: string, reason: string}> */
    private function exclusionList(): array
    {
        return [
            [
                'role_a' => 'super-admin',
                'role_b' => 'branch-manager',
                'level' => 'ABSOLUTE',
                'reason' => 'super-admin es rol global exclusivo — no puede combinarse con roles de sucursal',
            ],
            [
                'role_a' => 'super-admin',
                'role_b' => 'pharmacist',
                'level' => 'ABSOLUTE',
                'reason' => 'super-admin es rol global exclusivo',
            ],
            [
                'role_a' => 'super-admin',
                'role_b' => 'cashier',
                'level' => 'ABSOLUTE',
                'reason' => 'super-admin es rol global exclusivo',
            ],
            [
                'role_a' => 'super-admin',
                'role_b' => 'purchasing',
                'level' => 'ABSOLUTE',
                'reason' => 'super-admin es rol global exclusivo',
            ],
            [
                'role_a' => 'super-admin',
                'role_b' => 'auditor',
                'level' => 'ABSOLUTE',
                'reason' => 'super-admin es rol global exclusivo',
            ],
            [
                'role_a' => 'cashier',
                'role_b' => 'auditor',
                'level' => 'ABSOLUTE',
                'reason' => 'El cajero no puede auditar sus propias transacciones',
            ],
            [
                'role_a' => 'pharmacist',
                'role_b' => 'purchasing',
                'level' => 'ABSOLUTE',
                'reason' => 'Quien dispensa no puede aprobar las compras que dispensa',
            ],
            [
                'role_a' => 'cashier',
                'role_b' => 'purchasing',
                'level' => 'ABSOLUTE',
                'reason' => 'Previene fraude: el cajero no puede crear órdenes que él mismo procesaría',
            ],
            [
                'role_a' => 'auditor',
                'role_b' => 'pharmacist',
                'level' => 'ABSOLUTE',
                'reason' => 'El auditor de dispensación no puede ser quien dispensa',
            ],
            [
                'role_a' => 'auditor',
                'role_b' => 'cashier',
                'level' => 'ABSOLUTE',
                'reason' => 'El auditor de ventas no puede ser quien vende',
            ],
            [
                'role_a' => 'auditor',
                'role_b' => 'purchasing',
                'level' => 'ABSOLUTE',
                'reason' => 'El auditor de inventario no puede gestionar compras',
            ],
            [
                'role_a' => 'branch-manager',
                'role_b' => 'auditor',
                'level' => 'RECOMMENDED',
                'reason' => 'El gerente no debería auditar sus propias operaciones de sucursal',
            ],
        ];
    }
}
