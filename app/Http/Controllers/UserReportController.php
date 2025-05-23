<?php

namespace App\Http\Controllers;

use App\Models\UserReport;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

class UserReportController extends Controller
{
    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return View
     */
    public function get(): View
    {
        $userReports = UserReport::where('status', 0)->get();
        // Ugly way of loading this relationship, eager loading with dynamic relations don't work!
        foreach ($userReports as $userReport) {
            $userReport->model;
        }

        return view('admin.userreport.list', ['models' => $userReports]);
    }
}
