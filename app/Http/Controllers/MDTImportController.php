<?php

namespace App\Http\Controllers;

use App\Logic\MDT\IO\ImportString;
use App\Logic\MDT\IO\ImportWarning;
use App\Models\AffixGroup;
use App\Models\MDTImport;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class MDTImportController extends Controller
{
    /**
     * Returns some details about the passed string.
     * @param Request $request
     * @param SeasonService $seasonService
     * @return array|void
     * @throws Exception
     * @throws Throwable
     */
    public function details(Request $request, SeasonService $seasonService)
    {
        $string = $request->get('import_string');

        $importString = new ImportString($seasonService);

        try {
            $warnings = new Collection();
            $dungeonRoute = $importString->setEncodedString($string)->getDungeonRoute($warnings, false, false);

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
                'faction'          => $dungeonRoute->faction->name,
                'dungeon'          => $dungeonRoute->dungeon !== null ? $dungeonRoute->dungeon->name : __('Unknown dungeon'),
                'affixes'          => $affixes,
                'pulls'            => $dungeonRoute->killzones->count(),
                'lines'            => $dungeonRoute->brushlines->count(),
                'notes'            => $dungeonRoute->mapicons->count(),
                'enemy_forces'     => $dungeonRoute->getEnemyForces(),
                'enemy_forces_max' => $dungeonRoute->teeming ? $dungeonRoute->dungeon->enemy_forces_required_teeming : $dungeonRoute->dungeon->enemy_forces_required,
                'warnings'         => $warningResult
            ];

            return $result;
        } catch (Exception $ex) {
            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('Invalid MDT string: %s'), $ex->getMessage());
            } else {
                $message = __('Invalid MDT string');
            }
            return abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, 'MDT importer is not configured properly. Please contact the admin about this issue.');
            }

            throw $error;
        }
    }

    /**
     * @param Request $request
     * @param SeasonService $seasonService
     * @return Factory|View|void
     * @throws Exception
     * @throws Throwable
     */
    public function import(Request $request, SeasonService $seasonService)
    {
        $user = Auth::user();

        $sandbox = (bool)$request->get('sandbox', false);
        // @TODO This should be handled differently imho
        if ($sandbox || $user->canCreateDungeonRoute()) {
            $string = $request->get('import_string');
            $importString = new ImportString($seasonService);

            try {
                // @TODO improve exception handling
                $warnings = new Collection();
                $dungeonRoute = $importString->setEncodedString($string)->getDungeonRoute($warnings, $sandbox, true);

                // Keep track of the import
                $mdtImport = new MDTImport();
                $mdtImport->dungeon_route_id = $dungeonRoute->id;
                $mdtImport->import_string = $string;
                $mdtImport->save();
            } catch (Exception $ex) {
                return abort(400, sprintf(__('Invalid MDT string: %s'), $ex->getMessage()));
            } catch (Throwable $error) {
                if ($error->getMessage() === "Class 'Lua' not found") {
                    return abort(500, 'MDT importer is not configured properly. Please contact the admin about this issue.');
                }

                throw $error;
            }
            if ($sandbox) {
                $result = view('dungeonroute.sandbox', ['model' => $dungeonRoute]);
            } else {
                $result = redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonRoute]);
            }
        } else {
            $result = view('dungeonroute.limitreached');
        }

        return $result;
    }
}
