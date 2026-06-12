<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PresentationsSeeder extends Seeder
{
    public function run(): void
    {
        $presentations = [
            ['name' => 'Tableta',                        'abbreviation' => 'Tab',     'description' => 'Forma sólida oral. Comprimido con dosis exacta de principio activo.'],
            ['name' => 'Tableta recubierta',              'abbreviation' => 'Tab.Rec', 'description' => 'Tableta con cubierta que protege el principio activo o mejora la deglución.'],
            ['name' => 'Cápsula',                        'abbreviation' => 'Cap',     'description' => 'Forma sólida oral con cubierta de gelatina dura o blanda.'],
            ['name' => 'Cápsula de liberación prolongada', 'abbreviation' => 'Cap.LP', 'description' => 'Cápsula diseñada para liberar el principio activo gradualmente.'],
            ['name' => 'Jarabe',                         'abbreviation' => 'Jar',     'description' => 'Solución oral azucarada con principio activo disuelto o disperso.'],
            ['name' => 'Solución inyectable',            'abbreviation' => 'Sol.Iny', 'description' => 'Preparación líquida estéril para administración parenteral.'],
            ['name' => 'Solución oral',                  'abbreviation' => 'Sol.Or',  'description' => 'Preparación líquida homogénea para administración oral.'],
            ['name' => 'Suspensión oral',                'abbreviation' => 'Susp',    'description' => 'Partículas sólidas dispersas en vehículo líquido para uso oral.'],
            ['name' => 'Crema',                          'abbreviation' => 'Crema',   'description' => 'Preparación semisólida de emulsión para uso tópico.'],
            ['name' => 'Ungüento',                       'abbreviation' => 'Ung',     'description' => 'Preparación semisólida de base grasa para uso tópico.'],
            ['name' => 'Gel',                            'abbreviation' => 'Gel',     'description' => 'Preparación semisólida de consistencia gelatinosa para uso tópico.'],
            ['name' => 'Óvulo',                          'abbreviation' => 'Óv',      'description' => 'Forma farmacéutica sólida para uso vaginal.'],
            ['name' => 'Supositorio',                    'abbreviation' => 'Sup',     'description' => 'Forma farmacéutica sólida para uso rectal.'],
            ['name' => 'Parche transdérmico',            'abbreviation' => 'Parche',  'description' => 'Sistema de liberación controlada a través de la piel.'],
            ['name' => 'Aerosol',                        'abbreviation' => 'Aerosol', 'description' => 'Preparación en envase presurizado para inhalación o uso tópico.'],
            ['name' => 'Polvo para reconstituir',        'abbreviation' => 'Polvo',   'description' => 'Polvo seco que se mezcla con agua antes de su administración.'],
            ['name' => 'Gotas oftálmicas',               'abbreviation' => 'Got.Oft', 'description' => 'Solución o suspensión estéril para administración ocular.'],
            ['name' => 'Gotas óticas',                   'abbreviation' => 'Got.Ot',  'description' => 'Solución o suspensión para administración en el oído.'],
            ['name' => 'Spray nasal',                    'abbreviation' => 'Spray',   'description' => 'Preparación para administración intranasal en forma de aerosol.'],
        ];

        foreach ($presentations as $data) {
            DB::table('presentations')->updateOrInsert(
                // [DB::raw('LOWER(abbreviation)') => mb_strtolower($data['abbreviation'])],
                ['abbreviation' => $data['abbreviation']],
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
