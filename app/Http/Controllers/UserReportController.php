<?php

namespace App\Http\Controllers;

use App\Models\UserReport;

class UserReportController extends Controller
{
    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('admin.userreport.list', ['models' => UserReport::all()]);
    }
}