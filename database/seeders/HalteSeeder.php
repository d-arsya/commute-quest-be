<?php

namespace Database\Seeders;

use App\Models\Halte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HalteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    function limitDecimalString($number, $precision = 5)
    {
        $parts = explode('.', $number);
        if (count($parts) === 2) {
            return $parts[0] . '.' . substr($parts[1], 0, $precision);
        }
        return $number;
    }

    public function run(): void
    {
        $path = public_path('data/halte.json');
        $json = file_get_contents($path);
        $data = json_decode($json, true);

        foreach ($data['data'] as $item) {
            $longitude = $this->limitDecimalString($item['longitude'], 5);
            $latitude = $this->limitDecimalString($item['latitude'], 5);
            Halte::create([
                'name' => $item['name'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'link' => "https://www.google.com/maps?q=$latitude,$longitude"
            ]);
        }
    }
}
