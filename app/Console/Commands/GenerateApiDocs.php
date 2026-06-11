<?php

// ── ARCHIVO: app/Console/Commands/GenerateApiDocs.php ──

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;

class GenerateApiDocs extends Command
{
    protected $signature = 'api:docs {--output=public/api-docs.yaml}';

    protected $description = 'Genera el archivo OpenAPI YAML desde los atributos de los controllers';

    public function handle(): int
    {
        $scanPath = app_path('Http/Controllers');
        $outputFile = base_path($this->option('output'));

        // $openapi = Generator::scan([$scanPath]);
        $openapi = (new Generator)->generate([$scanPath]);
        // file_put_contents($outputFile, $openapi->toYaml());
        file_put_contents($outputFile, $openapi->toYaml());

        $this->info("✔ OpenAPI generado en: {$outputFile}");

        return self::SUCCESS;
    }
}
