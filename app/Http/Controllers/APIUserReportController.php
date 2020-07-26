<?php

namespace App\Http\Controllers;


use App\Http\Requests\UserReportFormRequest;
use App\Models\DungeonRoute;
use App\Models\Enemy;
use App\Models\UserReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class APIUserReportController
{
    /**
     * @param Request $request
     * @return UserReport|UserReport[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function markasresolved(Request $request)
    {
        $userReport = UserReport::findOrFail($request->get('id'));
        $userReport->status = 1;

        if (!$userReport->save()) {
            abort(500, 'Unable to save report!');
        }

        return $userReport;
    }

    /**
     * @param UserReportFormRequest $request
     * @param Model $model
     * @return bool
     */
    private function store(UserReportFormRequest $request, Model $model)
    {
        $userReport = new UserReport();
        $userReport->model_id = $model->id;
        $userReport->model_class = get_class($model);
        $userReport->user_id = Auth::id() ?? -1;
        // May be null if user was not logged in, this is fine
        $userReport->username = $request->get('username', null);
        $userReport->category = $request->get('category');
        $userReport->message = $request->get('message', '');
        $userReport->contact_ok = $request->get('contact_ok', false);
        $userReport->status = 0;

        return $userReport->save();
    }

    /**
     * @param UserReportFormRequest $request
     * @param DungeonRoute $dungeonroute
     *
     * @return \Illuminate\Http\Response
     */
    public function dungeonrouteStore(UserReportFormRequest $request, DungeonRoute $dungeonroute)
    {
        if (!$this->store($request, $dungeonroute)) {
            abort(500, 'Unable to save report!');
        } else {
            // Message to the user
//            Session::flash('status', __('Report created. I will look at your report soon and resolve the situation. Thank you!'));
        }

        return response()->noContent();
    }

    /**
     * @param UserReportFormRequest $request
     * @param Enemy $enemy
     *
     * @return \Illuminate\Http\Response
     */
    public function enemyStore(UserReportFormRequest $request, Enemy $enemy)
    {
        if (!$this->store($request, $enemy)) {
            abort(500, 'Unable to save report!');
        } else {
            // Message to the user
//            Session::flash('status', __('Enemy bug reported. I will look at your report soon and resolve the situation. Thank you!'));
        }

        return response()->noContent();
    }
}