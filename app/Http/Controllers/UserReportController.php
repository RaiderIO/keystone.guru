<?php

namespace App\Http\Controllers;

use App\Models\UserReport;
use Illuminate\Contracts\View\Factory;

class UserReportController extends Controller
{
    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        return view('admin.userreport.list', ['models' => UserReport::where('status', 0)->get()]);
    }
}