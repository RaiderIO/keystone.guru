<?php

namespace App\Http\Controllers;

use App\Http\Requests\NpcFormRequest;
use App\Models\Npc;
use App\Models\NpcClassification;
use Illuminate\Http\Request;
use Teapot\StatusCode\Http;

class PatreonController extends Controller
{

    /**
     * Checks if the incoming request is a save as new request or not.
     * @param Request $request
     * @return bool
     */
    public function link(Request $request)
    {
        $state = $request->get('state');
        $code = $request->get('code');

        // If session was not expired
        if( csrf_token() === $state ){

        } else {
            response('Your session has expired. Please try again.', Http::FORBIDDEN);
        }
    }
}
