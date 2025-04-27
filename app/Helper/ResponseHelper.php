<?php

namespace App\Helper;

class ResponseHelper
{
    public static function send($message, $data = null, $code = 200)
    {
        return response()->json([
            'success' => $code < 300,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
