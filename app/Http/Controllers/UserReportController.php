<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserReportFormRequest;
use App\Models\UserReport;
use Illuminate\Support\Facades\Auth;

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

    public function store(UserReportFormRequest $request)
    {
        $userReport = new UserReport();
        $userReport->author_id = Auth::user() === null ? -1 : Auth::user()->id;
        $userReport->category = $request->get('userreport_category');
        $userReport->context = $request->get('userreport_context');
        // May be null if user was not logged in, this is fine
        $userReport->username = $request->get('userreport_username');
        $userReport->message = $request->get('userreport_message', '');

        if (!$userReport->save()) {
            abort(500, 'Unable to save report!');
        } else {
            // Message to the user
            \Session::flash('status', __('Report created. I will look at your report soon and resolve the situation. Thank you!'));
        }

        return redirect()->back();
    }
}