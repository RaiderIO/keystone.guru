<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\Feature\Feature;
use App\Models\User;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Purge every stored value first, so that each user's feature re-resolves against their own roles the
        // next time it's checked, instead of blanket-flipping their already-cached rows to the new switch value
        PennantFeature::purge($feature);

        $adminUser = User::findOrFail(Feature::ADMIN_USER_ID);
        if ($wasActive) {
            PennantFeature::for($adminUser)->deactivate($feature);
        } else {
            PennantFeature::for($adminUser)->activate($feature);
        }

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
