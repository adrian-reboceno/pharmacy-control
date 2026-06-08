<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,  // 6 roles + 62 permisos + role_metadata
            SoDExclusionsSeeder::class,         // 12 restricciones SSoD + usuario sistema
        ]);
    }
}
