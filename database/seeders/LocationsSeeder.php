<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['name' => 'OTC General',   'description' => 'Medicamentos sin receta de venta general'],
            ['name' => 'Refrigeración', 'description' => 'Productos que requieren cadena de frío'],
            ['name' => 'Controlados',   'description' => 'Medicamentos sujetos a control especial'],
        ];

        foreach ($zones as $zoneData) {
            $zoneId = (string) Str::uuid();

            DB::table('locations')->insert([
                'id' => $zoneId,
                'name' => $zoneData['name'],
                'level' => 1,
                'parent_id' => null,
                'description' => $zoneData['description'],
                'is_active' => true,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            for ($p = 1; $p <= 2; $p++) {
                $pasilloId = (string) Str::uuid();

                DB::table('locations')->insert([
                    'id' => $pasilloId,
                    'name' => "Pasillo {$p}",
                    'level' => 2,
                    'parent_id' => $zoneId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                for ($e = 1; $e <= 3; $e++) {
                    $estandeId = (string) Str::uuid();

                    DB::table('locations')->insert([
                        'id' => $estandeId,
                        'name' => "Estante {$e}",
                        'level' => 3,
                        'parent_id' => $pasilloId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    for ($pos = 1; $pos <= 5; $pos++) {
                        DB::table('locations')->insert([
                            'id' => (string) Str::uuid(),
                            'name' => "Pos {$pos}",
                            'level' => 4,
                            'parent_id' => $estandeId,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
        // Total: 3 zonas × 2 pasillos × 3 estantes × 5 posiciones = 90 posiciones + 18 nodos intermedios
    }
}
