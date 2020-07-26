<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserReportFormRequest;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\UserReport;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Session;

class UserReportController extends Controller
{
    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        return view('admin.userreport.list', ['models' => UserReport::all()]);
    }
}