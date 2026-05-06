<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\AjaxViewFormRequest;

class AjaxViewController extends Controller
{
    public function view(AjaxViewFormRequest $request, string $view): string
    {
        // $request->validated() now includes 'view' from the URL
        return view('layouts.ajax', [
            'view'  => $view,
            'async' => true,
        ])->render();
    }
}
