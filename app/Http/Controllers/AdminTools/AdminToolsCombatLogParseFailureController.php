<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ResolvesCombatLogParseFailureSegments;
use App\Models\CombatLog\CombatLogParseFailure;
use App\Service\RaiderIO\RaiderIOApiServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AdminToolsCombatLogParseFailureController extends Controller
{
    use ResolvesCombatLogParseFailureSegments;

    private const int MAX_RESULTS = 500;

    public function index(): View
    {
        $failures = CombatLogParseFailure::query()
            ->orderBy('resolved_at')
            ->orderByDesc('created_at')
            ->limit(self::MAX_RESULTS)
            ->get();

        return view('admin.tools.combatlog.parsefailures', [
            'failures' => $failures,
        ]);
    }

    public function segments(RaiderIOApiServiceInterface $raiderIOApiService, CombatLogParseFailure $parseFailure): JsonResponse
    {
        return $this->resolveCombatLogParseFailureSegments($raiderIOApiService, $parseFailure);
    }

    public function resolve(CombatLogParseFailure $parseFailure): RedirectResponse
    {
        $parseFailure->update(['resolved_at' => now()]);

        Session::flash('status', __('controller.admintools.flash.combatlog_parse_failure_resolved'));

        return redirect()->route('admin.tools.combatlog.parsefailures.view');
    }
}
