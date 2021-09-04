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
        $userReports = UserReport::where('status', 0)->get();
        // Ugly way of loading this relationship, eager loading with dynamic relations don't work!
        foreach ($userReports as $userReport) {
            $userReport->model;
        }
        return view('admin.userreport.list', ['models' => $userReports]);
    }
}
