<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * @property int      $id
 * @property int      $user_id
 * @property int      $model_id
 * @property string   $model_class
 * @property string   $session_id
 * @property int|null $source
 * @property Carbon   $created_at
 * @property Carbon   $updated_at
 *
 * @mixin Eloquent
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
     *              can be inserted instead.
     */
    public function isRecent(): bool
    {
        return $this->created_at->isAfter(Carbon::now()->subMinutes(config('keystoneguru.view_time_threshold_mins')));
    }

    /**
     * Tracks a view for this model. The view may not track if there's a recent view, and we're still in the same 'session'.
     *
     * @return bool True if the page view was tracked, false if it was not.
     */
    public static function trackPageView(int $modelId, string $modelClass, ?int $source = null): bool
    {
        $result = false;

        $userId = Auth::id() ?: -1;
        // PHP session ID for keeping track of guests
        $sessionId = Session::getId();

        $mostRecentPageView = PageView::getMostRecentPageView($userId, $sessionId, $modelId, $modelClass);

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
        }

        return $result;
    }

    /**
     * @param  int           $userId The user's ID, or -1 for a guest
     * @return PageView|null The most recent page view, or null if none was found.
     */
    private static function getMostRecentPageView(int $userId, string $sessionId, int $modelId, string $modelClass): ?PageView
    {
        return PageView::query()
            ->where('user_id', $userId)
            ->where('model_id', $modelId)
            ->where('model_class', $modelClass)
            ->where('session_id', $sessionId)
            ->latest()
            ->first();
    }
}
