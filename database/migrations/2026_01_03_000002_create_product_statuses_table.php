<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_statuses', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 50)->notNull();
            $table->string('code', 20)->notNull();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index('is_active');
        });

        DB::statement('CREATE UNIQUE INDEX product_statuses_name_lower_unique ON product_statuses (LOWER(name))');
        DB::statement('CREATE UNIQUE INDEX product_statuses_code_lower_unique ON product_statuses (LOWER(code))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_statuses_name_lower_unique');
        DB::statement('DROP INDEX IF EXISTS product_statuses_code_lower_unique');
        Schema::dropIfExists('product_statuses');
    }
};
