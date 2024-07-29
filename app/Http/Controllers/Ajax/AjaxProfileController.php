<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AjaxProfileController extends Controller
{
    public function legalAgree(Request $request): Response
    {
        $time = $request->get('time', -1);

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'legal_agreed'    => 1,
            'legal_agreed_ms' => $time,
        ]);

        return response()->noContent();
    }
}
