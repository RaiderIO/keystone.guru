<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\CombatLog\CombatLogParsingCriterion;
use App\Models\Interfaces\CombatLogCriterionModelInterface;
use App\Service\CombatLog\CombatLogParsingCriteriaServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Session;

class AdminToolsCombatLogCriteriaController extends Controller
{
    public function criteria(): View
    {
        $today = Carbon::now()->toDateString();

        $criteria = CombatLogParsingCriterion::query()
            ->where('date', $today)
            ->orderBy('combat_log_version')
            ->orderBy('model_class')
            ->get();

        $modelsById = [];
        foreach (CombatLogParsingCriterion::VALID_CRITERIA as $modelClass => $with) {
            $ids = $criteria->where('model_class', $modelClass)->pluck('model_id');

            /** @var class-string<CombatLogCriterionModelInterface|Model> $modelClass */
            $modelsById[$modelClass] = $modelClass::query()
                ->with($with)
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id');
        }

        return view('admin.tools.combatlog.criteria', [
            'criteriaByVersion' => $criteria->groupBy('combat_log_version'),
            'modelsById'        => $modelsById,
        ]);
    }

    public function updateThresholds(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'thresholds'   => ['required', 'array'],
            'thresholds.*' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['thresholds'] as $criterionId => $threshold) {
            CombatLogParsingCriterion::query()
                ->where('id', (int)$criterionId)
                ->update(['threshold' => $threshold]);
        }

        Session::flash('status', __('controller.admintools.flash.combatlog_criteria_thresholds_updated'));

        return redirect()->route('admin.tools.combatlog.criteria.view');
    }

    public function criteriaReset(CombatLogParsingCriteriaServiceInterface $criteriaService): RedirectResponse
    {
        $criteriaService->resetAllForToday();

        Session::flash('status', __('controller.admintools.flash.combatlog_criteria_reset'));

        return redirect()->route('admin.tools.combatlog.criteria.view');
    }
}
