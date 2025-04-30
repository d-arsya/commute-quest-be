<?php

namespace Database\Seeders;

use App\Models\Bus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = public_path('data/rute.json');
        $json = file_get_contents($path);
        $data = json_decode($json, true);

        foreach ($data['data'] as $item) {
            Bus::create([
                'name' => $item['nama'],
                'routes' => $item['rute']
            ]);
        }
    }
}
