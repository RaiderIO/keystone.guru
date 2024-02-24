<?php

namespace App\Http\Controllers\Ajax;


use App\Http\Requests\UserReportFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\UserReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AjaxUserReportController
{
    /**
     * @return Model|UserReport
     */
    public function status(Request $request, UserReport $userreport)
    {
        $userreport->status = $request->get('status', 0);

        if (!$userreport->save()) {
            abort(500, __('controller.apiuserreport.error.unable_to_update_user_report'));
        }

        return $userreport;
    }

    /**
     * @return bool
     */
    private function store(UserReportFormRequest $request, Model $model): bool
    {
        $userReport              = new UserReport();
        $userReport->model_id    = $model->id;
        $userReport->model_class = $model::class;
        $userReport->user_id     = Auth::id() ?? -1;
        // May be null if user was not logged in, this is fine
        $userReport->username   = $request->get('username', null);
        $userReport->category   = $request->get('category');
        $userReport->message    = $request->get('message', '');
        $userReport->contact_ok = $request->get('contact_ok', false);
        $userReport->status     = 0;

        $saveResult = $userReport->save();
        if ($saveResult) {
            Log::info('New user report', [
                'category'    => $userReport->category,
                'message'     => $userReport->message,
                'model'       => $userReport->model_id,
                'model_class' => $userReport->model_class,
            ]);
        }

        return $saveResult;
    }

    /**
     *
     * @return Response
     */
    public function dungeonrouteStore(UserReportFormRequest $request, DungeonRoute $dungeonroute)
    {
        if (!$this->store($request, $dungeonroute)) {
            abort(500, __('controller.apiuserreport.error.unable_to_save_report'));
        }

        return response()->noContent();
    }

    /**
     *
     * @return Response
     */
    public function enemyStore(UserReportFormRequest $request, Enemy $enemy)
    {
        if (!$this->store($request, $enemy)) {
            abort(500, __('controller.apiuserreport.error.unable_to_save_report'));
        }

        return response()->noContent();
    }
}
