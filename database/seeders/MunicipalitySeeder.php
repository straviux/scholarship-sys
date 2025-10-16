<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\Barangay;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = resource_path('js/Data/municipalities.json');
        $municipalitiesData = json_decode(file_get_contents($jsonPath), true);

        foreach ($municipalitiesData['municipalities'] as $municipalityData) {
            $municipality = Municipality::create([
                'name' => $municipalityData['name'],
            ]);

            foreach ($municipalityData['barangays'] as $barangayName) {
                Barangay::create([
                    'municipality_id' => $municipality->id,
                    'name' => $barangayName,
                ]);
            }
        }

        $this->command->info('Municipalities and Barangays seeded successfully!');
    }
}
