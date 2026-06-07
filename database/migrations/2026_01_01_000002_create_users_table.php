<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 15)->nullable()->comment('NULL: teléfono opcional en el registro inicial');
            $table->string('status', 30)->default('PENDING_VERIFICATION');
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable()->comment('NULL: se rellena solo al configurar 2FA; AES-256 vía Laravel Crypt, nunca en claro');
            $table->boolean('must_change_password')->default(true);
            $table->timestampTz('password_changed_at')->nullable()->comment('NULL hasta el primer cambio de contraseña');
            $table->smallInteger('failed_login_attempts')->default(0);
            $table->timestampTz('locked_until')->nullable()->comment('NULL cuando la cuenta no está bloqueada');
            $table->timestampTz('last_login_at')->nullable()->comment('NULL en cuentas nunca usadas');
            $table->timestampTz('last_activity_at')->nullable()->comment('NULL hasta el primer request autenticado; control de inactividad');
            $table->timestampTz('email_verified_at')->nullable()->comment('NULL hasta verificar el email');
            $table->string('email_verification_token', 64)->nullable()->comment('NULL tras verificación; token temporal');
            $table->timestampTz('deleted_at')->nullable()->comment('Soft delete — nunca hard delete en usuarios con historial');
            $table->uuid('created_by')->nullable()->comment('NULL: el primer super-admin no tiene creador');
            $table->timestampsTz();

            $table->index('status');
            $table->index('deleted_at');
            $table->index('locked_until');
            $table->index('email_verification_token');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check
            CHECK (status IN ('ACTIVE','INACTIVE','LOCKED','PENDING_VERIFICATION'))");

        // FK self-referencial añadida después del CREATE para evitar referencia circular
        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
        });
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check');
        Schema::dropIfExists('users');
    }
};
