<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
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

        $wasActive = Feature::active($feature);
        if ($wasActive) {
            Feature::deactivateForEveryone($feature);
        } else {
            Feature::activateForEveryone($feature);
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

        Feature::forget($feature);
        Feature::for(null)->forget($feature);

        Session::flash('status', __('controller.admintools.flash.feature_forgotten', ['feature' => $feature]));

        return redirect()->route('admin.tools.features.list');
    }
}
