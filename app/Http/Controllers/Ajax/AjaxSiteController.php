<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxSiteController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function refreshCsrf(Request $request)
    {
        session()->regenerate();

        return response()->json([
            'token' => csrf_token(),
        ]);
    }
}
