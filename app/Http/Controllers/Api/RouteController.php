<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Halte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/halte",
     *     summary="Get all halte data",
     *     tags={"Halte"},
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve all haltes data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve all haltes data"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="AA YKPN"),
     *                     @OA\Property(property="latitude", type="string", example="-7.78614"),
     *                     @OA\Property(property="longitude", type="string", example="110.38347"),
     *                     @OA\Property(property="link", type="string", example="https://www.google.com/maps?q=-7.78614,110.38347")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Something went wrong"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */

    public function getAllHalte()
    {
        $haltes = Halte::all();
        return ResponseHelper::send('Success retrieve all haltes data', $haltes, 200);
    }
    /**
     * @OA\Post(
     *     path="/halte",
     *     summary="Get detail of a specific halte",
     *     tags={"Halte"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="AA YKPN")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve halte data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve halte data"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="AA YKPN"),
     *                 @OA\Property(property="latitude", type="string", example="-7.78614"),
     *                 @OA\Property(property="longitude", type="string", example="110.38347"),
     *                 @OA\Property(property="link", type="string", example="https://www.google.com/maps?q=-7.78614,110.38347"),
     *                 @OA\Property(
     *                     property="buses",
     *                     type="array",
     *                     @OA\Items(type="string", example="1A")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Your input is invalid"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Halte not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Halte not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */

    public function getHalteDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'exists:haltes,name'],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        }
        $halte = Halte::where('name', $request->name)->first();
        $halte->buses = $halte->buses;
        return ResponseHelper::send('Success retrieve halte data', $halte, 200);
    }
    /**
     * @OA\Post(
     *     path="/bus",
     *     summary="Get detail of a specific bus route",
     *     tags={"Bus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="1A")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve bus data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve bus data"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="name", type="string", example="1A"),
     *                 @OA\Property(
     *                     property="routes",
     *                     type="array",
     *                     @OA\Items(type="string", example="Bandara Adisutjipto")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Your input is invalid"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Bus not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Bus not found"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     )
     * )
     */

    public function getBusDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'exists:buses,name'],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        }
        $bus = Bus::where('name', $request->name)->first();
        return ResponseHelper::send('Success retrieve bus data', $bus, 200);
    }
    /**
     * @OA\Get(
     *     path="/bus",
     *     summary="Get all bus data",
     *     tags={"Bus"},
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve all buses data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve all buses data"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="1A"),
     *                     @OA\Property(
     *                         property="routes",
     *                         type="array",
     *                         @OA\Items(type="string", example="Bandara Adisutjipto")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Something went wrong"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */

    public function getAllBus()
    {
        $buses = Bus::all();
        return ResponseHelper::send('Success retrieve all buses data', $buses, 200);
    }
}
