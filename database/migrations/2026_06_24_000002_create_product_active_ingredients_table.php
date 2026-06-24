<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_active_ingredients', function (Blueprint $table): void {
            $table->uuid('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->uuid('ingredient_id');
            $table->foreign('ingredient_id')->references('id')->on('active_ingredients');
            $table->string('concentration', 30)->notNull();
            $table->string('concentration_unit', 20)->notNull();
            $table->primary(['product_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_active_ingredients');
    }
};
