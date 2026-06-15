<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,       // 6 roles + 62 permisos + role_metadata
            SoDExclusionsSeeder::class,              // 12 restricciones SSoD + usuario sistema
            UnitsOfMeasurementSeeder::class,         // 20 unidades de medida predefinidas
            PresentationsSeeder::class,              // 19 formas farmacéuticas predefinidas
            RoutesOfAdministrationSeeder::class,     // 13 vías de administración predefinidas
            ProductStatusesSeeder::class,            // 3 estados de producto predefinidos
        ]);
    }
}
