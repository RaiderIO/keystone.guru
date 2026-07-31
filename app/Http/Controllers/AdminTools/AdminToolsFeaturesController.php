<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\Feature\Feature;
use App\Models\User;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Laravel\Pennant\Feature as PennantFeature;
use Session;

class AdminToolsFeaturesController extends Controller
{
    public function listFeatures(Request $request): View
    {
        return view('admin.tools.features.list', [
            'features' => collect(ClassFinder::getClassesInNamespace('App\\Features')),
        ]);
    }

    public function toggleFeature(Request $request): RedirectResponse
    {
        $feature = (string)$request->get('feature');

        $wasActive = Feature::getAdminValue($feature);

        // Purge every stored value and set the new switch value in one transaction - otherwise a request that
        // resolves this feature in the gap between the purge and the set would read a momentarily-missing switch
        // row as an admin-disabled feature, and (for an otherwise-entitled user) persist that stale 'false' again
        DB::transaction(function () use ($feature, $wasActive): void {
            PennantFeature::purge($feature);

            $adminUser = User::findOrFail(Feature::ADMIN_USER_ID);
            if ($wasActive) {
                PennantFeature::for($adminUser)->deactivate($feature);
            } else {
                PennantFeature::for($adminUser)->activate($feature);
            }
        });

        Session::flash('status', __(!$wasActive ?
            'controller.admintools.flash.feature_toggle_activated' :
            'controller.admintools.flash.feature_toggle_deactivated', [
                'feature' => $feature,
            ]));

        return redirect()->route('admin.tools.features.list');
    }

    public function forgetFeature(Request $request): RedirectResponse
    {
        $feature = (string)$request->get('feature');

        PennantFeature::forget($feature);
        PennantFeature::for(null)->forget($feature);

        Session::flash('status', __('controller.admintools.flash.feature_forgotten', ['feature' => $feature]));

        return redirect()->route('admin.tools.features.list');
    }
}
