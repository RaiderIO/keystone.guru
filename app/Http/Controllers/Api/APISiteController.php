<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class APISiteController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshCsrf(Request $request)
    {
        session()->regenerate();

        return response()->json([
            'token' => csrf_token()
        ]);
    }
}
