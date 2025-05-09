<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use SKAgarwal\GoogleApi\PlacesNew\GooglePlaces;
use yidas\googleMaps\Client;

class MapsController extends Controller
{
    /**
     * @OA\Post(
     *     path="/nearest-halte",
     *     tags={"Route"},
     *     summary="Get Nearest Haltes",
     *     description="Find the nearest halte(s) from given coordinates. Requires bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lat","lng"},
     *             @OA\Property(property="lat", type="number", format="float", example=-7.77432),
     *             @OA\Property(property="lng", type="number", format="float", example=110.37051),
     *             @OA\Property(property="count", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve nearest haltes data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve nearest haltes data"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="lat", type="number", example=-7.77432),
     *                     @OA\Property(property="lng", type="number", example=110.37051)
     *                 ),
     *                 @OA\Property(property="haltes", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=129),
     *                         @OA\Property(property="name", type="string", example="Kopma UGM"),
     *                         @OA\Property(property="latitude", type="string", example="-7.77432"),
     *                         @OA\Property(property="longitude", type="string", example="110.37512"),
     *                         @OA\Property(property="link", type="string", format="uri", example="https://www.google.com/maps?q=-7.77432,110.37512"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-09 15:40:34"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-09 15:40:34"),
     *                         @OA\Property(property="distance", type="integer", example=724),
     *                         @OA\Property(
     *                             property="coords",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="array",
     *                                 @OA\Items(type="number")
     *                             ),
     *                             example={
     *                                 {110.37053, -7.774260000000001},
     *                                 {110.37074000000001, -7.7743}
     *                             }
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your input is invalid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */

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
                $halte->coords = $this->decodePolyline($route["polyline"]["encodedPolyline"]);
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

    private function decodePolyline($polyline)
    {
        $index = 0;
        $points = [];
        $lat = 0;
        $lng = 0;

        while ($index < strlen($polyline)) {
            $shift = 0;
            $result = 0;

            do {
                $byte = ord($polyline[$index++]) - 63;
                $result |= ($byte & 0x1F) << $shift;
                $shift += 5;
            } while ($byte >= 0x20);

            $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lat += $dlat;

            $shift = 0;
            $result = 0;

            do {
                $byte = ord($polyline[$index++]) - 63;
                $result |= ($byte & 0x1F) << $shift;
                $shift += 5;
            } while ($byte >= 0x20);

            $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $dlng;

            $points[] = [$lng * 1e-5, $lat * 1e-5];
        }

        return $points;
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
            ],
            ["travelMode" => "TRANSIT"]
        );
        return $routes["routes"][0];
    }
    /**
     * @OA\Post(
     *     path="/auto-complete",
     *     tags={"Route"},
     *     summary="Get autocomplete suggestions for location search",
     *     description="Returns list of predicted places based on input text, including coordinates.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", example="Stasiun Bogor")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve nearest haltes data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve nearest haltes data"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="coords", type="object",
     *                         @OA\Property(property="latitude", type="number", format="float", example=-6.5956203),
     *                         @OA\Property(property="longitude", type="number", format="float", example=106.7897462),
     *                     ),
     *                     @OA\Property(property="placeId", type="string", example="ChIJy-YfMAPFaS4R3A9KhGYqX0c"),
     *                     @OA\Property(property="text", type="string", example="Stasiun Bogor, RW.06, Cibogor, Bogor City, West Java, Indonesia")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Your input is invalid"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function autoCompletion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        }
        $response = GooglePlaces::make()->autocomplete($request->text);

        $data = collect($response->collect()["suggestions"]);
        $data = $data->map(function ($location) {
            $location["coords"] = $this->placeDetails($location["placePrediction"]["placeId"]);
            $location["placeId"] = $location["placePrediction"]["placeId"];
            $location["text"] = $location["placePrediction"]["text"]["text"];
            unset($location["placePrediction"]);
            return $location;
        });
        return ResponseHelper::send('Success retrieve nearest haltes data', $data, 200);
    }
    public function placeDetails(string $placeId)
    {
        $response = GooglePlaces::make()->placeDetails($placeId);

        $data = $response->collect();
        return $data["location"];
    }
    /**
     * @OA\Post(
     *     path="/get-route",
     *     summary="Get route data between origin and destination",
     *     description="Retrieve route information including distance, duration, and polyline between two coordinates using Google Maps API.",
     *     operationId="postRoute",
     *     tags={"Route"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"originLatitude", "originLongitude", "destinationLatitude", "destinationLongitude"},
     *             @OA\Property(property="originLatitude", type="number", format="float", example=-7.77388),
     *             @OA\Property(property="originLongitude", type="number", format="float", example=110.37258),
     *             @OA\Property(property="destinationLatitude", type="number", format="float", example=-7.76743),
     *             @OA\Property(property="destinationLongitude", type="number", format="float", example=110.37891)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve routes data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve routes data"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="origin",
     *                     type="object",
     *                     @OA\Property(property="latitude", type="number", format="float", example=-7.77388),
     *                     @OA\Property(property="longitude", type="number", format="float", example=110.37258)
     *                 ),
     *                 @OA\Property(
     *                     property="destination",
     *                     type="object",
     *                     @OA\Property(property="latitude", type="number", format="float", example=-7.76743),
     *                     @OA\Property(property="longitude", type="number", format="float", example=110.37891)
     *                 ),
     *                 @OA\Property(property="distance", type="integer", example=1456),
     *                 @OA\Property(property="duration", type="string", example="302s"),
     *                 @OA\Property(
     *                     property="routes",
     *                     type="array",
     *                     @OA\Items(
     *                         type="array",
     *                         @OA\Items(type="number", format="float")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function getRoute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'originLatitude' => 'required|decimal:5',
            'originLongitude' => 'required|decimal:5',
            'destinationLatitude' => 'required|decimal:5',
            'destinationLongitude' => 'required|decimal:5',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        }
        $gmaps = new Client(['key' => env('MAPS_API_KEY')]);
        $routes = $gmaps->computeRoutes(
            [
                "location" => [
                    "latLng" => [
                        "latitude" => $request->originLatitude,
                        "longitude" => $request->originLongitude
                    ]
                ]
            ],
            [
                "location" => [
                    "latLng" => [
                        "latitude" => $request->destinationLatitude,
                        "longitude" => $request->destinationLongitude
                    ]
                ]
            ],
            [
                "travelMode" => "TRANSIT",
                "transitPreferences" => [
                    "allowedTravelModes" => ["BUS"],
                    "routingPreference" => "LESS_WALKING"
                ],
                "computeAlternativeRoutes" => true
            ],
            // ["routes.*"]
        );
        $route = $this->decodePolyline($routes["routes"][0]["polyline"]["encodedPolyline"]);
        $data = [
            "origin" => [
                "latitude" => $request->originLatitude,
                "longitude" => $request->originLongitude
            ],
            "destination" => [
                "latitude" => $request->destinationLatitude,
                "longitude" => $request->destinationLongitude
            ],
            "distance" => $routes["routes"][0]["distanceMeters"],
            "duration" => $routes["routes"][0]["duration"],
            "routes" => $route
        ];
        return ResponseHelper::send('Success retrieve routes data', $routes, 200);
    }
    /**
     * @OA\Post(
     *     path="/get-maps-link",
     *     summary="Generate a Google Maps direction link",
     *     description="Returns a Google Maps direction URL using the given origin and destination coordinates.",
     *     operationId="getMapsLink",
     *     tags={"Route"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={
     *                 "originLatitude", 
     *                 "originLongitude", 
     *                 "destinationLatitude", 
     *                 "destinationLongitude"
     *             },
     *             @OA\Property(property="originLatitude", type="number", format="float", example=-7.77142),
     *             @OA\Property(property="originLongitude", type="number", format="float", example=110.38329),
     *             @OA\Property(property="destinationLatitude", type="number", format="float", example=-7.77142),
     *             @OA\Property(property="destinationLongitude", type="number", format="float", example=110.36561)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve routes data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve routes data"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="link",
     *                     type="string",
     *                     example="https://www.google.com/maps/dir/?api=1&origin=-7.77142,110.38329&destination=-7.77142,110.36561&travelmode=transit"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    public function getMapsLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'originLatitude' => 'required|decimal:5',
            'originLongitude' => 'required|decimal:5',
            'destinationLatitude' => 'required|decimal:5',
            'destinationLongitude' => 'required|decimal:5',
        ]);
        if ($validator->fails()) {
            return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        }
        return ResponseHelper::send('Success retrieve routes data', ["link" => "https://www.google.com/maps/dir/?api=1&origin={$request->originLatitude},{$request->originLongitude}&destination={$request->originLatitude},{$request->destinationLongitude}&travelmode=transit"], 200);
    }
}
