<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductStatusesSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Activo',
                'code' => 'ACTIVO',
                'description' => 'El producto está disponible para venta y movimientos de inventario.',
            ],
            [
                'name' => 'Descontinuado',
                'code' => 'DESCONTINUADO',
                'description' => 'El producto ya no se comercializa pero conserva su historial e inventario existente.',
            ],
            [
                'name' => 'Eliminado',
                'code' => 'ELIMINADO',
                'description' => 'El producto fue eliminado lógicamente del catálogo activo.',
            ],
        ];

        foreach ($statuses as $data) {
            DB::table('product_statuses')->updateOrInsert(
                ['code' => $data['code']],
                array_merge($data, [
                    'id' => (string) Str::uuid(),
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
