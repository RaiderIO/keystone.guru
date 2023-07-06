<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * @property int $id
 * @property int $user_id
 * @property int $model_id
 * @property string $model_class
 * @property string $session_id
 * @property int|null $source
 * @property string $created_at
 * @property string $updated_at
 *
 * @mixin \Eloquent
 */
class PageView extends Model
{
    protected $fillable = [
        'user_id',
        'model_id',
        'model_class',
        'session_id',
        'source',
    ];

    /**
     * @return bool True if this PageView is recent enough to be considered 'current', false if it is not and a new view
     * can be inserted instead.
     */
    public function isRecent(): bool
    {
        // If the previous page view was created at least view_time_threshold_mins minutes ago.
        return Carbon::createFromTimeString($this->created_at)
            ->subMinutes(config('keystoneguru.view_time_threshold_mins'))
            ->isPast();
    }

    /**
     * Tracks a view for this model. The view may not track if there's a recent view, and we're still in the same 'session'.
     * @param int $modelId
     * @param string $modelClass
     * @param int|null $source
     * @return bool True if the page view was tracked, false if it was not.
     */
    public static function trackPageView(int $modelId, string $modelClass, int $source = null): bool
    {
        $result = false;

        $userId = Auth::id() ?: -1;
        // PHP session ID for keeping track of guests
        $sessionId = Session::getId();

        $mostRecentPageView = PageView::getMostRecentPageView($modelId, $modelClass);

        // Only if the view may be counted
        if ($mostRecentPageView === null || !$mostRecentPageView->isRecent()) {
            // Create a new view and save it
            PageView::create([
                'user_id'     => $userId,
                'model_id'    => $modelId,
                'model_class' => $modelClass,
                'session_id'  => $sessionId,
                'source'      => $source,
            ]);

            $result = true;
        } else {
            // Keep track of when it was updated
            $mostRecentPageView->updated_at = Carbon::now()->toDateTimeString();
            $mostRecentPageView->save();
        }

        return $result;
    }

    /**
     * Checks if the view may be counted or if it shouldn't be counted because a previously existing view is too recent.
     * @param $modelId int
     * @param $modelClass string
     * @return PageView|null The most recent page view, or null if none was found.
     */
    private static function getMostRecentPageView(int $modelId, string $modelClass): ?PageView
    {
        $userId = Auth::id();
        // PHP session ID for keeping track of guests
        $sessionId = Session::getId();

        /** @var Collection $existingPageViews */
        return PageView::where('user_id', $userId)
            ->where('model_id', $modelId)
            ->where('model_class', $modelClass)
            ->where('session_id', $sessionId)->first();
    }
}
