<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxSiteController extends Controller
{
    public function refreshCsrf(Request $request): JsonResponse
    {
        session()->regenerate();

        return response()->json([
            'token' => csrf_token(),
        ]);
    }
}
