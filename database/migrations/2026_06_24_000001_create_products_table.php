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
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('type', 10)->notNull();
            $table->string('name', 200)->notNull();
            $table->text('description')->nullable();
            $table->string('sale_condition', 30)->notNull();
            $table->string('sanitary_reg', 50)->nullable();
            $table->string('barcode', 13)->nullable();

            $table->uuid('status_id')->notNull();
            $table->foreign('status_id')->references('id')->on('product_statuses');
            $table->uuid('category_id')->notNull();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->uuid('laboratory_id')->nullable();
            $table->foreign('laboratory_id')->references('id')->on('laboratories')->nullOnDelete();
            $table->uuid('unit_id')->notNull();
            $table->foreign('unit_id')->references('id')->on('units_of_measurement');
            $table->uuid('presentation_id')->notNull();
            $table->foreign('presentation_id')->references('id')->on('presentations');
            $table->uuid('route_id')->notNull();
            $table->foreign('route_id')->references('id')->on('routes_of_administration');
            $table->uuid('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();

            $table->unsignedSmallInteger('units_per_box')->default(1);
            $table->unsignedSmallInteger('units_per_blister')->default(1);

            $table->unsignedInteger('min_stock')->default(0);
            $table->unsignedInteger('max_stock')->default(100);
            $table->unsignedSmallInteger('expiry_alert_days')->default(30);
            $table->boolean('manage_lots')->default(true);
            $table->boolean('allow_fraction')->default(false);

            $table->decimal('retail_margin', 5, 2)->default(0);
            $table->decimal('wholesale_margin', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('type');
            $table->index('status_id');
            $table->index('category_id');
            $table->index('is_active');
        });

        DB::statement("CREATE UNIQUE INDEX products_name_lower_unique ON products (LOWER(name))");
        DB::statement("CREATE UNIQUE INDEX products_barcode_unique ON products (barcode) WHERE barcode IS NOT NULL");
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (type IN ('GENERIC','BRANDED'))");
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_sale_condition_check CHECK (sale_condition IN ('SIN_RECETA','CON_RECETA','CON_RECETA_RETENIDA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
