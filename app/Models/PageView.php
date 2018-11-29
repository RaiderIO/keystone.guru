<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * @property int $id
 * @property int $user_id
 * @property int $model_id
 * @property string $model_class
 * @property string $session_id
 * @property string $created_at
 * @property string $updated_at
 */
class PageView extends Model
{

    /**
     * @return bool True if this PageView is recent enough to be considered 'current', false if it is not and a new view
     * can be inserted instead.
     */
    public function isRecent()
    {
        // If the previous page view was created at least view_time_threshold_mins minutes ago.
        return Carbon::createFromTimeString($this->created_at)
            ->subMinute(config('keystoneguru.view_time_threshold_mins'))
            ->isPast();
    }

    /**
     * Tracks a view for this model. The view may not track if there's a recent view and we're still in the same 'session'.
     * @param $modelId int
     * @param $modelClass string
     */
    public static function trackPageView($modelId, $modelClass)
    {
        $userId = Auth::id();
        // PHP session ID for keeping track of guests
        $sessionId = Session::getId();

        $mostRecentPageView = PageView::getMostRecentPageView($modelId, $modelClass);
        // Only if the view may be counted
        if ($mostRecentPageView === null || !$mostRecentPageView->isRecent()) {
            // Create a new view and save it
            $pageView = new PageView();
            $pageView->user_id = $userId;
            $pageView->model_id = $modelId;
            $pageView->model_class = $modelClass;
            $pageView->session_id = $sessionId;
            $pageView->save();
        } else {
            // Keep track of when it was updated
            $mostRecentPageView->updated_at = \Illuminate\Support\Carbon::now()->toDateTimeString();
            $mostRecentPageView->save();
        }
    }

    /**
     * Checks if the view may be counted or if it shouldn't be counted because a previously existing view is too recent.
     * @param $modelId int
     * @param $modelClass string
     * @return PageView The most recent page view, or null if none was found.
     */
    private static function getMostRecentPageView($modelId, $modelClass)
    {
        $userId = Auth::id();
        // PHP session ID for keeping track of guests
        $sessionId = Session::getId();
        DB::enableQueryLog();

        /** @var Collection $existingPageViews */
        $existingPageViews = PageView::where('user_id', $userId)
            ->where('model_id', $modelId)
            ->where('model_class', $modelClass)
            ->where('session_id', $sessionId)->get();

        dd(DB::getQueryLog());

        return $existingPageViews->first();
    }
}
