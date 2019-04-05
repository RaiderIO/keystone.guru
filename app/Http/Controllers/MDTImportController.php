<?php

namespace App\Http\Controllers;

use App\Logic\MDT\IO\ImportString;
use App\Logic\MDT\IO\ImportWarning;
use App\Models\AffixGroup;
use App\Models\MDTImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MDTImportController extends Controller
{
    /**
     * Returns some details about the passed string.
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function details(Request $request)
    {
        $string = $request->get('import_string');

        $importString = new ImportString();

        try {
            $warnings = new Collection();
            $dungeonRoute = $importString->setEncodedString($string)->getDungeonRoute($warnings, false);

            $affixes = [];
            foreach ($dungeonRoute->affixes as $affixGroup) {
                /** @var $affixGroup AffixGroup */
                $affixes[] = $affixGroup->getTextAttribute();
            }

            $warningResult = [];
            foreach ($warnings as $warning) {
                /** @var $warning ImportWarning */
                $warningResult[] = $warning->toArray();
            }

            $result = [
                // Siege of Boralus faction
                'faction' => $dungeonRoute->faction->name,
                'dungeon' => $dungeonRoute->dungeon !== null ? $dungeonRoute->dungeon->name : __('Unknown dungeon'),
                'affixes' => $affixes,
                'pulls' => $dungeonRoute->killzones->count(),
                'lines' => $dungeonRoute->brushlines->count(),
                'notes' => $dungeonRoute->mapcomments->count(),
                'enemy_forces' => $dungeonRoute->getEnemyForcesAttribute(),
                'enemy_forces_max' => $dungeonRoute->hasTeemingAffix() ? $dungeonRoute->dungeon->enemy_forces_required_teeming : $dungeonRoute->dungeon->enemy_forces_required,
                'warnings' => $warningResult
            ];

            return $result;
        } catch (\Exception $ex) {
            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('Invalid MDT string: %s'), $ex->getMessage());
            } else {
                $message = __('Invalid MDT string');
            }
            return abort(400, $message);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function import(Request $request)
    {
        $user = Auth::user();

        // @TODO This should be handled differently imho
        if ($user->canCreateDungeonRoute()) {
            $string = $request->get('import_string');
            $importString = new ImportString();

            try {
                // @TODO improve exception handling
                $warnings = new Collection();
                $dungeonRoute = $importString->setEncodedString($string)->getDungeonRoute($warnings, true);

                // Keep track of the import
                $mdtImport = new MDTImport();
                $mdtImport->dungeon_route_id = $dungeonRoute->id;
                $mdtImport->import_string = $string;
                $mdtImport->save();
            } catch (\Exception $ex) {
                abort(400, sprintf(__('Invalid MDT string: %s'), $ex->getMessage()));
            }

            $result = redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonRoute]);
        } else {
            $result = view('dungeonroute.limitreached');
        }

        return $result;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function view()
    {
        return view('admin.tools.datadump.mdtstring');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function viewstring(Request $request)
    {
        $string = $request->get('import_string');
        $importString = new ImportString();
        echo json_encode($importString->setEncodedString($string)->getDecoded());
    }
}
