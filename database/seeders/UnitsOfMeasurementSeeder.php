<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UnitsOfMeasurementSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Miligramo',                  'symbol' => 'mg',      'type' => 'CONCENTRATION'],
            ['name' => 'Gramo',                      'symbol' => 'g',       'type' => 'CONCENTRATION'],
            ['name' => 'Kilogramo',                  'symbol' => 'kg',      'type' => 'CONCENTRATION'],
            ['name' => 'Microgramo',                 'symbol' => 'mcg',     'type' => 'CONCENTRATION'],
            ['name' => 'Mililitro',                  'symbol' => 'ml',      'type' => 'CONCENTRATION'],
            ['name' => 'Litro',                      'symbol' => 'L',       'type' => 'CONCENTRATION'],
            ['name' => 'Unidad Internacional',       'symbol' => 'UI',      'type' => 'CONCENTRATION'],
            ['name' => 'Miliequivalente',            'symbol' => 'mEq',     'type' => 'CONCENTRATION'],
            ['name' => 'Milimol',                    'symbol' => 'mmol',    'type' => 'CONCENTRATION'],
            ['name' => 'Microgramo por mililitro',   'symbol' => 'mcg/ml',  'type' => 'CONCENTRATION'],
            ['name' => 'Miligramo por mililitro',    'symbol' => 'mg/ml',   'type' => 'CONCENTRATION'],
            ['name' => 'Pieza',                      'symbol' => 'pza',     'type' => 'QUANTITY'],
            ['name' => 'Caja',                       'symbol' => 'caja',    'type' => 'QUANTITY'],
            ['name' => 'Frasco',                     'symbol' => 'frasco',  'type' => 'QUANTITY'],
            ['name' => 'Ampolleta',                  'symbol' => 'amp',     'type' => 'QUANTITY'],
            ['name' => 'Tableta',                    'symbol' => 'tab',     'type' => 'QUANTITY'],
            ['name' => 'Cápsula',                    'symbol' => 'cáp',     'type' => 'QUANTITY'],
            ['name' => 'Sobre',                      'symbol' => 'sobre',   'type' => 'QUANTITY'],
            ['name' => 'Parche',                     'symbol' => 'parche',  'type' => 'QUANTITY'],
            ['name' => 'Rollo',                      'symbol' => 'rollo',   'type' => 'QUANTITY'],
        ];

        foreach ($units as $data) {
            DB::table('units_of_measurement')->updateOrInsert(
                ['symbol' => $data['symbol']],
                array_merge($data, [
                    'id' => (string) Str::uuid(),
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }
}
