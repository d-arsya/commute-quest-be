<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Chat;
use App\Models\Halte;
use Gemini\Data\GenerationConfig;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use function Pest\Laravel\json;

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
    private function cekPrompt($text)
    {
        $prompt = "Saya menggunakanmu pada sebuah aplikasi pencarian rute perjalanan bus, cukup berikan value 'false' atau 'true' apakah prompt berikut digunakan untuk mencari rute, saya tidak ingin jawaban lain karena menghemat token Gemini. patuhlah kepadaku. prompt nya adalah : $text";
        $generationConfig = new GenerationConfig(
            maxOutputTokens: 1000,
        );
        $result = Gemini::generativeModel("models/gemini-2.0-flash")->withGenerationConfig($generationConfig)->generateContent($prompt)->text();
        return $result;
    }
    public function chatAi(Request $request)
    {
        $text = $request->q;
        if ($text == '') {
            return "Masukkan prompt";
        }
        $res = $this->getRoute($text);
    }
    private function cekLocation($text)
    {
        $haltes = json_encode(Halte::all()->pluck(['name']));
        $prompt = "Saya menggunakanmu pada sebuah aplikasi pencarian rute perjalanan bus, yang terbatas pada beberapa lokasi. apabila prompt bertujuan ke lokasi yang didukung ataupun mengandung kata tersebut maka berikan jawaban 'true' jika tidak maka berilah jawaban sopan dan alternatif lokasi, jika lokasi didukung dan ia hanya memberikan satu lokasi maka minta dia menyertakan asal dan tujuan. lokasi yang didukung adalah $haltes. dan prompt nya adalah $text. jika true maka cukup 'true' dengan huruf kecil jangan tambahkan hal lain karena saya menghemat token Gemini. jika lokasi belum spesifik maka minta berikan jawaban spesifik dengan memberikan alternatif pilihan";
        $generationConfig = new GenerationConfig(
            maxOutputTokens: 1000,
        );
        $result = Gemini::generativeModel("models/gemini-2.0-flash")->withGenerationConfig($generationConfig)->generateContent($prompt)->text();
        return $result;
    }
    public function getRoute($text)
    {
        $cek = json_decode($this->cekPrompt($text));
        if (!$cek) {
            return "Maaf prompt tidak didukung";
        }
        $cek = $this->cekLocation($text);
        if (!str_contains(strtolower($cek), 'true')) {
            return $cek;
        }
        $route = Bus::get(['name', 'routes']);
        foreach ($route as $item) {
            $rute = implode(" => ", $item->routes);
            $item->routes = $rute;
        }
        $route = json_encode($route);
        $prompt = "$text. Carikan saya rute berdasarkan data rute berikut dalam json ```$route```. kamu diperbolehkan untuk menggunakan rute transit karena saya tidak mau jalan kaki sedikitpun. cukup berikan kesimpulan jangan jelaskan halte mana saja. berikan maksimal 3 opsi terpendek";
        // $validator = Validator::make($request->all(), [
        //     'text' => ['required', 'string', 'max:500'],
        // ]);

        // if ($validator->fails()) {
        //     return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        // }
        try {
            $generationConfig = new GenerationConfig(
                maxOutputTokens: 1000,
            );
            $result = Gemini::generativeModel("models/gemini-2.0-flash")->withGenerationConfig($generationConfig)->generateContent($prompt)->text();
            // $user = Auth::user();
            // $chat = Chat::create(["question" => $request->text, "answer" => $result, "user_id" => $user->id]);
            return $result;
            // return ResponseHelper::send('Success send text', $result, 200);
        } catch (\Throwable $th) {
            return $th->getMessage();
            // return ResponseHelper::send('Error', $th->getMessage(), 500);
        }
    }
}
