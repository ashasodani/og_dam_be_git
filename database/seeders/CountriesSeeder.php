<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Countries;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            'Colombia', 'Ecuador', 'Chile','Chile-D', 'Mexico', 'GUATEMALA', 'VENEZUELA', 'Peru', 'Saudi Arabia', 'United Kingdom', 'Brazil', 'Kenya',
            'Port Sudan', 'Indonesia'
        ];

        foreach ($countries as $country) {
            Countries::firstOrCreate(
                ['country_name' => $country],
                ['sheet_name' => strtoupper($country).'-D']
            );
        }
    }
}
