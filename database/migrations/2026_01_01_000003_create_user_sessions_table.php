<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id');
            $table->string('client_type', 10);
            $table->string('access_token_hash', 255)->unique();
            $table->string('refresh_token_hash', 255)->unique();
            $table->uuid('active_role_id');
            $table->uuid('active_branch_id')->nullable()->comment('NULL para roles globales (super-admin, auditor)');
            $table->timestampTz('access_expires_at');
            $table->timestampTz('refresh_expires_at');
            $table->timestampTz('last_activity_at');
            $table->timestampTz('role_activated_at');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable()->comment('NULL: algunos clientes no envían user-agent');
            $table->timestampTz('revoked_at')->nullable()->comment('NULL = sesión vigente; con valor = revocada manualmente');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('active_role_id')->references('id')->on('roles')->restrictOnDelete();

            $table->index(['user_id', 'client_type']);
            $table->index('revoked_at');
            $table->index('refresh_expires_at');
        });

        DB::statement("ALTER TABLE user_sessions ADD CONSTRAINT user_sessions_client_type_check
            CHECK (client_type IN ('WEB','MOBILE'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE user_sessions DROP CONSTRAINT IF EXISTS user_sessions_client_type_check');
        Schema::dropIfExists('user_sessions');
    }
};
