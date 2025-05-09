<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use yidas\googleMaps\Client;

class MapsController extends Controller
{
    public function getNearestHalte(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|decimal:5',
            'lng' => 'required|decimal:5',
            'count' => 'integer'
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        }
        $lat = $request->lat;
        $lng = $request->lng;

        $count = $request->count ?? 1;

        $nearest = DB::table('haltes')
            ->select('*')
            ->selectRaw("(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [
                $lat,
                $lng,
                $lat
            ])
            ->orderBy('distance')
            ->limit(10)->get()->map(function ($halte) use ($lat, $lng) {
                $route = $this->calculateDistance($lat, $lng, $halte->latitude, $halte->longitude);
                $halte->distance = $route["distanceMeters"];
                $halte->polyline = $route["polyline"]["encodedPolyline"];
                return $halte;
            })->sortBy('distance')   // sort berdasarkan distance hasil route API
            ->values()             // reset index agar mulai dari 0
            ->take($count);
        $data = [
            "user" => [
                "lat" => $lat,
                "lng" => $lng
            ],
            "haltes" => $nearest
        ];

        return ResponseHelper::send('Success retrieve nearest haltes data', $data, 200);
    }

    private function calculateDistance($orlat, $orlng, $delat, $delng)
    {
        $gmaps = new Client(['key' => env('MAPS_API_KEY')]);
        $routes = $gmaps->computeRoutes(
            [
                "location" => [
                    "latLng" => [
                        "latitude" => $orlat,
                        "longitude" => $orlng
                    ]
                ]
            ],
            [
                "location" => [
                    "latLng" => [
                        "latitude" => $delat,
                        "longitude" => $delng
                    ]
                ]
            ]
        );
        return $routes["routes"][0];
    }
}
