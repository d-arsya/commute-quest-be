<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Chat;
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
            $generationConfig = new GenerationConfig(
                maxOutputTokens: 1000,
            );
            $result = Gemini::generativeModel("models/gemini-2.0-flash")->withGenerationConfig($generationConfig)->generateContent($request->text)->text();
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
}
