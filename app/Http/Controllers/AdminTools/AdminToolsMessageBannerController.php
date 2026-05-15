<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Service\MessageBanner\MessageBannerServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class AdminToolsMessageBannerController extends Controller
{
    public function messageBanner(): View
    {
        return view('admin.tools.messagebanner.set');
    }

    public function messageBannerSubmit(
        Request                       $request,
        MessageBannerServiceInterface $messageBannerService,
    ): RedirectResponse {
        $message = $request->get('message');
        $messageBannerService->setMessage(empty($message) ? null : $message);

        Session::flash('status', __('controller.admintools.flash.message_banner_set_successfully'));

        return redirect()->route('admin.tools.messagebanner.set');
    }
}
