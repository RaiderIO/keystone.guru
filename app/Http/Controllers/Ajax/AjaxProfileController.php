<?php

namespace App\Http\Controllers\Ajax;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AjaxProfileController
{
    /**
     * @return Response
     */
    public function legalAgree(Request $request): Response
    {
        $time = $request->get('time', -1);

        $user                  = Auth::user();
        $user->legal_agreed    = 1;
        $user->legal_agreed_ms = $time;

        $user->save();

        return response()->noContent();
    }
}
