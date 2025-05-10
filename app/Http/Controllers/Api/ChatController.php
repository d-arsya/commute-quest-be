<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\Chat;
use App\Models\Halte;
use App\Models\User;
use Gemini\Data\GenerationConfig;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * @OA\Post(
     *     path="/chat",
     *     summary="Send a chat request to the Gemini model",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", maxLength=500, example="Apa itu machine learning?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success send text",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success send text"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={
     *                     "question": "Apa itu machine learning?",
     *                     "answer": "Machine learning adalah cabang dari kecerdasan buatan...",
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Your input is invalid"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 example={"text": {"The text field is required."}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Error"),
     *             @OA\Property(property="data", type="string", example="Exception message here")
     *         )
     *     )
     * )
     */

    public function chatRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return ResponseHelper::send('Your input is invalid', $validator->messages(), 400);
        }
        try {
            $result = $this->getRoute($request->text);
            $user = Auth::user();
            $chat = Chat::create(["question" => $request->text, "answer" => $result, "user_id" => $user->id]);
            return ResponseHelper::send('Success send text', $chat, 200);
        } catch (\Throwable $th) {
            return ResponseHelper::send('Error', $th->getMessage(), 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/chat",
     *     summary="Retrieve authenticated user's chat history",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success retrieve all chat history",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success retrieve all chat history"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="question", type="string", example="Apa itu AI?"),
     *                     @OA\Property(property="answer", type="string", example="AI adalah singkatan dari Artificial Intelligence...")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */

    public function chatHistory(Request $request)
    {
        $user = Auth::user();
        $chats = $user->chats;
        return ResponseHelper::send('Success retrieve all chat history', $chats, 200);
    }
    /**
     * @OA\Delete(
     *     path="/chat",
     *     summary="Delete all chat history of the authenticated user",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success delete all chat history",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success delete all chat history"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */

    public function chatClear(Request $request)
    {
        User::find(Auth::user()->id)->chats()->delete();
        return ResponseHelper::send('Success delete all chat history', null, 200);
    }
    /**
     * @OA\Delete(
     *     path="/chat/{chat}",
     *     tags={"Chat"},
     *     summary="Delete specific chat history",
     *     description="Delete a chat history by ID for the authenticated user. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat",
     *         in="path",
     *         required=true,
     *         description="ID of the chat to be deleted",
     *         @OA\Schema(type="string", example="15")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success delete chat history",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success delete chat history"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     )
     * )
     */

    public function destroy(Request $request, string $chat)
    {
        User::find(Auth::user()->id)->chats()->where('id', $chat)->delete();
        return ResponseHelper::send('Success delete chat history', null, 200);
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

        try {
            $generationConfig = new GenerationConfig(
                maxOutputTokens: 1000,
            );
            $result = Gemini::generativeModel("models/gemini-2.0-flash")->withGenerationConfig($generationConfig)->generateContent($prompt)->text();
            return $result;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
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
}
