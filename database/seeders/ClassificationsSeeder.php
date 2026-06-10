<?php

// ── ARCHIVO: database/seeders/ClassificationsSeeder.php ──
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ClassificationsSeeder extends Seeder
{
    public function run(): void
    {
        $classifications = [
            [
                'lgs_group' => 'I',
                'name' => 'Estupefacientes',
                'prescription_type' => 'CON_CODIGO_BARRAS',
                'validity_days' => 30,
                'validity_note' => 'Receta con código de barras. Surtido en 1 sola ocasión.',
            ],
            [
                'lgs_group' => 'II',
                'name' => 'Psicotrópicos — Grupo II',
                'prescription_type' => 'NORMAL',
                'validity_days' => 30,
                'validity_note' => 'Máximo 2 presentaciones por receta. Surtido en 1 sola ocasión.',
            ],
            [
                'lgs_group' => 'III',
                'name' => 'Psicotrópicos — Grupo III',
                'prescription_type' => 'NORMAL',
                'validity_days' => 180,
                'validity_note' => 'Sin restricción de presentaciones. Se surte hasta 3 ocasiones (3 sellos).',
            ],
            [
                'lgs_group' => 'IV_A',
                'name' => 'Antibióticos',
                'prescription_type' => 'NORMAL',
                'validity_days' => null,
                'validity_note' => 'Vigencia: duración del tratamiento indicado por el médico.',
            ],
            [
                'lgs_group' => 'IV_B',
                'name' => 'Medicamentos con receta',
                'prescription_type' => 'NORMAL',
                'validity_days' => null,
                'validity_note' => 'Sin vigencia establecida. Tantas veces como indique el médico.',
            ],
            [
                'lgs_group' => 'V',
                'name' => 'OTC exclusivo farmacias',
                'prescription_type' => 'SIN_RECETA',
                'validity_days' => null,
                'validity_note' => 'Sin receta. Solo puede adquirirse en farmacias.',
            ],
            [
                'lgs_group' => 'VI',
                'name' => 'OTC libre acceso',
                'prescription_type' => 'SIN_RECETA',
                'validity_days' => null,
                'validity_note' => 'Sin receta. Puede adquirirse en cualquier comercio formalmente establecido.',
            ],
        ];

        foreach ($classifications as $data) {
            DB::table('medication_classifications')->updateOrInsert(
                ['lgs_group' => $data['lgs_group']],
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
