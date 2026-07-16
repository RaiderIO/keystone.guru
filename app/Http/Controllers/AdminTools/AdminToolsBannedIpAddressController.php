<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsBannedIpAddressStoreRequest;
use App\Models\BannedIpAddress;
use App\Service\BannedIpAddress\BannedIpAddressServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;

class AdminToolsBannedIpAddressController extends Controller
{
    public function index(): View
    {
        return view('admin.tools.bannedipaddresses.list', [
            'bannedIpAddresses' => BannedIpAddress::query()
                ->with('createdBy')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(
        AdminToolsBannedIpAddressStoreRequest $request,
        BannedIpAddressServiceInterface       $bannedIpAddressService,
    ): RedirectResponse {
        $bannedIpAddressService->ban(
            $request->string('ip_address')->toString(),
            $request->string('reason')->toString() === '' ? null : $request->string('reason')->toString(),
            $request->filled('expires_at') ? Carbon::parse($request->string('expires_at')->toString()) : null,
            (int)Auth::id(),
        );

        Session::flash('status', __('controller.admintools.flash.banned_ip_address_added'));

        return redirect()->route('admin.tools.bannedipaddresses.view');
    }

    public function destroy(
        BannedIpAddress                 $bannedIpAddress,
        BannedIpAddressServiceInterface $bannedIpAddressService,
    ): RedirectResponse {
        $bannedIpAddressService->unban($bannedIpAddress);

        Session::flash('status', __('controller.admintools.flash.banned_ip_address_removed'));

        return redirect()->route('admin.tools.bannedipaddresses.view');
    }
}
