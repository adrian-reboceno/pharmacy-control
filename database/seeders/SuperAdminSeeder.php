<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * SuperAdminSeeder
 *
 * Crea el usuario Super Administrador inicial del sistema.
 *
 * REQUISITOS (correr en este orden):
 *   1. php artisan migrate
 *   2. php artisan db:seed --class=RolesAndPermissionsSeeder
 *   3. php artisan db:seed --class=SuperAdminSeeder   ← este
 *
 * IDEMPOTENTE: re-ejecutable sin fallar ni duplicar datos.
 * En producción cambiar las credenciales desde .env antes de ejecutar.
 *
 * Variables de entorno reconocidas:
 *   SUPER_ADMIN_EMAIL      default: admin@pharmaco.mx
 *   SUPER_ADMIN_PASSWORD   default: (valor seguro — CAMBIAR antes de deploy)
 *   SUPER_ADMIN_FIRST_NAME default: Super
 *   SUPER_ADMIN_LAST_NAME  default: Admin
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * UUID nil RFC 4122 — representa "el sistema" como creador del primer usuario.
     * El primer super-admin no tiene creador humano (created_by = null).
     */
    private const SYSTEM_USER_ID = '00000000-0000-0000-0000-000000000000';

    public function run(): void
    {
        $email     = env('SUPER_ADMIN_EMAIL',      'admin@pharmaco.mx');
        $password  = env('SUPER_ADMIN_PASSWORD',   'Ch4ng3M3_N0w!#2026');
        $firstName = env('SUPER_ADMIN_FIRST_NAME', 'Super');
        $lastName  = env('SUPER_ADMIN_LAST_NAME',  'Admin');

        // ── 1. Crear o recuperar el usuario ───────────────────────────────────
        $user = DB::table('users')->where('email', $email)->first();

        if ($user === null) {
            $userId = Str::uuid()->toString();

            DB::table('users')->insert([
                'id'                       => $userId,
                'email'                    => $email,
                'password_hash'            => Hash::make($password),
                'first_name'               => $firstName,
                'last_name'                => $lastName,
                'phone'                    => null,
                'status'                   => 'ACTIVE',
                'two_factor_enabled'       => false,
                'two_factor_secret'        => null,
                // must_change_password = true obliga a cambiar en el primer login.
                // En producción dejar en true. Solo false para entornos de prueba.
                'must_change_password'     => (bool) env('SUPER_ADMIN_SKIP_PASSWORD_CHANGE', false),
                'password_changed_at'      => now(),
                'failed_login_attempts'    => 0,
                'locked_until'             => null,
                'last_login_at'            => null,
                'last_activity_at'         => null,
                // Email pre-verificado — el super-admin no necesita verificación de correo.
                'email_verified_at'        => now(),
                'email_verification_token' => null,
                'deleted_at'               => null,
                // El primer super-admin no tiene creador humano.
                'created_by'               => null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);

            $this->command->info("✅ Usuario super-admin creado: {$email}");

        } else {
            $userId = $user->id;
            $this->command->warn("⚠️  Usuario ya existe: {$email} — omitiendo creación.");
        }

        // ── 2. Asignar el rol super-admin ─────────────────────────────────────
        $role = Role::where('name', 'super-admin')
                    ->where('guard_name', 'api')
                    ->first();

        if ($role === null) {
            $this->command->error(
                '❌ Rol super-admin no encontrado. ' .
                'Ejecuta RolesAndPermissionsSeeder primero.'
            );
            return;
        }

        // Verificar si ya tiene el rol asignado (idempotencia)
        $alreadyAssigned = DB::table('model_has_roles')
            ->where('role_id',    $role->id)
            ->where('model_type', 'App\\Models\\User')
            ->where('model_uuid', $userId)
            ->exists();

        if (! $alreadyAssigned) {
            DB::table('model_has_roles')->insert([
                'role_id'     => $role->id,
                'model_type'  => 'App\\Models\\User',
                'model_uuid'  => $userId,
                // branch_id = null: super-admin es rol global, sin scope de sucursal.
                'branch_id'   => null,
                // assigned_by = null: el sistema asigna el primer super-admin.
                'assigned_by' => null,
                'assigned_at' => now(),
                // expires_at = null: sin expiración.
                'expires_at'  => null,
            ]);

            $this->command->info('✅ Rol super-admin asignado correctamente.');
        } else {
            $this->command->warn('⚠️  Rol super-admin ya estaba asignado — omitiendo.');
        }

        // ── 3. Limpiar caché de permisos Spatie ───────────────────────────────
        app(\Spatie\Permission\PermissionRegistrar::class)
            ->forgetCachedPermissions();

        // ── 4. Resumen en consola ─────────────────────────────────────────────
        $this->command->newLine();
        $this->command->table(
            ['Campo', 'Valor'],
            [
                ['Email',     $email],
                ['Password',  env('SUPER_ADMIN_PASSWORD') ? '*** (desde .env)' : $password . '  ⚠️  CAMBIAR en producción'],
                ['Rol',       'super-admin (nivel 10 — acceso total)'],
                ['Status',    'ACTIVE'],
                ['2FA',       'Desactivado — activar tras el primer login'],
                ['Branch',    'Global — sin restricción de sucursal'],
            ]
        );

        if (! env('SUPER_ADMIN_PASSWORD')) {
            $this->command->newLine();
            $this->command->warn(
                '⚠️  IMPORTANTE: estás usando la contraseña por defecto.' . PHP_EOL .
                '   Define SUPER_ADMIN_PASSWORD en .env antes de ejecutar en producción.'
            );
        }
    }
}