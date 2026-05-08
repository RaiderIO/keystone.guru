<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use Artisan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminToolsDataDumpController extends Controller
{
    public function exportdungeondata(Request $request): View
    {
        Artisan::call('mapping:save');

        return view('admin.tools.datadump.viewexporteddungeondata');
    }
}
