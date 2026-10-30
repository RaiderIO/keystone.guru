<?php

namespace App\Http\Controllers;

use App\Models\Notification\NotificationMessage;
use App\Service\Notification\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;

class NotificationController extends Controller
{
    private const int FEED_PAGE_SIZE = 20;

    public function index(Request $request, NotificationServiceInterface $notificationService): View
    {
        $user            = Auth::user();
        $beforeMessageId = $request->integer('before') === 0 ? null : $request->integer('before');

        $notifications = $notificationService->getFeed($user, self::FEED_PAGE_SIZE, $beforeMessageId);

        return view('notification.index', [
            'notifications' => $notifications,
            // Only offer an older page when this one filled up; the feed merges two sources, so
            // there is no total to count against
            'olderBeforeId' => $notifications->count() === self::FEED_PAGE_SIZE
                ? $notifications->last()->notificationMessageId
                : null,
        ]);
    }

    public function read(NotificationMessage $notificationMessage, NotificationServiceInterface $notificationService): RedirectResponse
    {
        if (!$notificationService->markRead(Auth::user(), $notificationMessage->id)) {
            abort(404);
        }

        return redirect()->route('notifications.index');
    }

    public function readAll(NotificationServiceInterface $notificationService): RedirectResponse
    {
        $notificationService->markAllRead(Auth::user());

        Session::flash('status', __('controller.notification.flash.all_marked_read'));

        return redirect()->route('notifications.index');
    }

    /**
     * Follows a notification's link, marking it read on the way.
     */
    public function go(NotificationMessage $notificationMessage, NotificationServiceInterface $notificationService): RedirectResponse
    {
        if (!$notificationService->markRead(Auth::user(), $notificationMessage->id)) {
            abort(404);
        }

        // Stored urls are validated to be relative when a notification is created; re-checking here
        // keeps a tampered row from turning this into an open redirect
        $url = $notificationMessage->url;

        if ($url === null || !str_starts_with($url, '/')) {
            return redirect()->route('notifications.index');
        }

        return redirect()->to($url);
    }
}
