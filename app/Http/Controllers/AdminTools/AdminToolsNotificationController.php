<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsNotificationStoreRequest;
use App\Models\Laratrust\Role;
use App\Models\Notification\NotificationMessage;
use App\Repositories\Interfaces\Notification\NotificationMessageRepositoryInterface;
use App\Service\Notification\NotificationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;

class AdminToolsNotificationController extends Controller
{
    private const int RECENT_LIMIT = 50;

    public function index(NotificationMessageRepositoryInterface $notificationMessageRepository): View
    {
        return view('admin.tools.notifications.list', [
            'notificationMessages' => $notificationMessageRepository->getRecentWithDeliveryCounts(self::RECENT_LIMIT),
            'roles'                => Role::all(),
        ]);
    }

    public function store(
        AdminToolsNotificationStoreRequest $request,
        NotificationServiceInterface       $notificationService,
    ): RedirectResponse {
        $notificationService->sendCustom(
            $request->getBody(),
            $request->getTarget(),
            (int)Auth::id(),
            $request->getUrl(),
            $request->getExpiresAt(),
        );

        Session::flash('status', __('controller.admintools.flash.notification_sent'));

        return redirect()->route('admin.tools.notifications.view');
    }

    public function destroy(
        NotificationMessage          $notificationMessage,
        NotificationServiceInterface $notificationService,
    ): RedirectResponse {
        $notificationService->retract($notificationMessage);

        Session::flash('status', __('controller.admintools.flash.notification_retracted'));

        return redirect()->route('admin.tools.notifications.view');
    }
}
