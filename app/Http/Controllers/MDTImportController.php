<?php

namespace App\Http\Controllers;

use App\Logic\MDT\ImportString;
use Illuminate\Http\Request;

class MDTImportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function import(Request $request)
    {
        $string = $request->get('import_string');

        $importString = new ImportString();
        
        $dungeonRoute = $importString->setEncodedString($string)->getDungeonRoute();


        return view('home');
    }
}
