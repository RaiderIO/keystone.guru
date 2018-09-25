<?php

namespace App\Http\Controllers;


use App\Models\UserReport;
use Illuminate\Http\Request;

class APIUserReportController
{
    /**
     * @param $request
     * @return array|mixed
     * @throws \Exception
     */
    public function store(Request $request)
    {
        $userReport = new UserReport();
        $userReport->id = $request->get('id');
        $userReport->author = \Auth::user()->id;
        $userReport->category = $request->get('category');
        $userReport->message = $request->get('message');

        if (!$userReport->save()) {
            abort(500, 'Unable to save report!');
        }

        return $userReport;
    }

    public function markasresolved(Request $request){
        $userReport = UserReport::findOrFail($request->get('id'));
        $userReport->handled = true;

        if (!$userReport->save()) {
            abort(500, 'Unable to save report!');
        }

        return $userReport;
    }
}