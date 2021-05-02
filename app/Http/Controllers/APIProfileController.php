<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Response;

class APIProfileController
{
    /**
     * @param Request $request
     * @return Response
     */
    public function legalAgree(Request $request)
    {
        $time = $request->get('time', -1);

        $user = Auth::user();
        $user->legal_agreed = 1;
        $user->legal_agreed_ms = $time;

        $user->save();

        return response()->noContent();
    }
}