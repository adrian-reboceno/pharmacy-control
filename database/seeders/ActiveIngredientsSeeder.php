<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ActiveIngredientsSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            // Antibióticos
            ['name' => 'Amoxicilina',                   'dci_code' => 'amoxicillin',              'cas_number' => '26787-78-0'],
            ['name' => 'Amoxicilina+Ácido Clavulánico', 'dci_code' => 'amoxicillin+clavulanate',  'cas_number' => null],
            ['name' => 'Azitromicina',                  'dci_code' => 'azithromycin',              'cas_number' => '83905-01-5'],
            ['name' => 'Ciprofloxacino',                'dci_code' => 'ciprofloxacin',             'cas_number' => '85721-33-1'],
            ['name' => 'Clindamicina',                  'dci_code' => 'clindamycin',               'cas_number' => '18323-44-9'],
            // Analgésicos / Antiinflamatorios
            ['name' => 'Paracetamol',                   'dci_code' => 'paracetamol',               'cas_number' => '103-90-2'],
            ['name' => 'Ibuprofeno',                    'dci_code' => 'ibuprofen',                 'cas_number' => '15687-27-1'],
            ['name' => 'Ácido Acetilsalicílico',        'dci_code' => 'acetylsalicylic acid',      'cas_number' => '50-78-2'],
            ['name' => 'Diclofenaco',                   'dci_code' => 'diclofenac',                'cas_number' => '15307-86-5'],
            ['name' => 'Naproxeno',                     'dci_code' => 'naproxen',                  'cas_number' => '22204-53-1'],
            ['name' => 'Ketorolaco',                    'dci_code' => 'ketorolac',                 'cas_number' => '74103-06-3'],
            // Antidiabéticos
            ['name' => 'Metformina',                    'dci_code' => 'metformin',                 'cas_number' => '657-24-9'],
            ['name' => 'Glibenclamida',                 'dci_code' => 'glibenclamide',             'cas_number' => '10238-21-8'],
            // Cardiovascular
            ['name' => 'Losartán',                      'dci_code' => 'losartan',                  'cas_number' => '114798-26-4'],
            ['name' => 'Atenolol',                      'dci_code' => 'atenolol',                  'cas_number' => '29122-68-7'],
            ['name' => 'Amlodipino',                    'dci_code' => 'amlodipine',                'cas_number' => '88150-42-9'],
            ['name' => 'Enalapril',                     'dci_code' => 'enalapril',                 'cas_number' => '75847-73-3'],
            // Gastrointestinal
            ['name' => 'Omeprazol',                     'dci_code' => 'omeprazole',                'cas_number' => '73590-58-6'],
            ['name' => 'Metoclopramida',                'dci_code' => 'metoclopramide',            'cas_number' => '364-62-5'],
            // Antihistamínicos
            ['name' => 'Loratadina',                    'dci_code' => 'loratadine',                'cas_number' => '79794-75-5'],
            ['name' => 'Cetirizina',                    'dci_code' => 'cetirizine',                'cas_number' => '83881-51-0'],
            // Otros
            ['name' => 'Dexametasona',                  'dci_code' => 'dexamethasone',             'cas_number' => '50-02-2'],
            ['name' => 'Prednisona',                    'dci_code' => 'prednisone',                'cas_number' => '53-03-2'],
            ['name' => 'Metronidazol',                  'dci_code' => 'metronidazole',             'cas_number' => '443-48-1'],
        ];

        foreach ($ingredients as $data) {
            $exists = DB::table('active_ingredients')
                ->whereRaw('LOWER(dci_code) = ?', [mb_strtolower($data['dci_code'])])
                ->exists();

            if (! $exists) {
                DB::table('active_ingredients')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'dci_code' => $data['dci_code'],
                    'cas_number' => $data['cas_number'],
                    'description' => null,
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
