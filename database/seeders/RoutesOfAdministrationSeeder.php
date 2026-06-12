<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RoutesOfAdministrationSeeder extends Seeder
{
    public function run(): void
    {
        $routes = [
            ['name' => 'Oral',          'code' => 'VO',  'description' => 'Administración por la boca, deglución del medicamento.'],
            ['name' => 'Sublingual',    'code' => 'SL',  'description' => 'Administración bajo la lengua para absorción rápida directa al torrente sanguíneo.'],
            ['name' => 'Intravenosa',   'code' => 'IV',  'description' => 'Administración directa al torrente sanguíneo a través de una vena.'],
            ['name' => 'Intramuscular', 'code' => 'IM',  'description' => 'Administración mediante inyección en el tejido muscular.'],
            ['name' => 'Subcutánea',    'code' => 'SC',  'description' => 'Administración mediante inyección en el tejido subcutáneo.'],
            ['name' => 'Tópica',        'code' => 'TOP', 'description' => 'Aplicación directa sobre la piel para efecto local.'],
            ['name' => 'Oftálmica',     'code' => 'OFT', 'description' => 'Administración directa en el ojo, generalmente en gotas o ungüento.'],
            ['name' => 'Ótica',         'code' => 'OT',  'description' => 'Administración directa en el oído, generalmente en gotas.'],
            ['name' => 'Nasal',         'code' => 'NAS', 'description' => 'Administración a través de las fosas nasales, en spray o gotas.'],
            ['name' => 'Rectal',        'code' => 'VR',  'description' => 'Administración a través del recto, en supositorio o enema.'],
            ['name' => 'Vaginal',       'code' => 'VAG', 'description' => 'Administración a través de la vagina, en óvulo, crema o tableta vaginal.'],
            ['name' => 'Inhalada',      'code' => 'INH', 'description' => 'Administración mediante inhalación a través de las vías respiratorias.'],
            ['name' => 'Transdérmica',  'code' => 'TD',  'description' => 'Administración a través de la piel mediante parches de liberación controlada.'],
        ];

        foreach ($routes as $data) {
            DB::table('routes_of_administration')->updateOrInsert(
                ['code' => $data['code']],
                array_merge($data, [
                    'id'         => (string) Str::uuid(),
                    'is_active'  => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
