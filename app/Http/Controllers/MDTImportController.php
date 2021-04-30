<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Requests\MDT\ImportStringFormRequest;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTString;
use App\Logic\MDT\IO\ImportString;
use App\Models\AffixGroup;
use App\Models\MDTImport;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Teapot\StatusCode;
use Throwable;

class MDTImportController extends Controller
{
    /**
     * Returns some details about the passed string.
     * @param ImportStringFormRequest $request
     * @param SeasonService $seasonService
     * @return array|void
     * @throws Exception
     * @throws Throwable
     */
    public function details(ImportStringFormRequest $request, SeasonService $seasonService)
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
                'dungeon'          => $dungeonRoute->dungeon !== null ? $dungeonRoute->dungeon->name : __('Unknown dungeon'),
                'affixes'          => $affixes,
                'pulls'            => $dungeonRoute->killzones->count(),
                'paths'            => $dungeonRoute->paths->count(),
                'lines'            => $dungeonRoute->brushlines->count(),
                'notes'            => $dungeonRoute->mapicons->count(),
                'enemy_forces'     => $dungeonRoute->enemy_forces,
                'enemy_forces_max' => $dungeonRoute->teeming ? $dungeonRoute->dungeon->enemy_forces_required_teeming : $dungeonRoute->dungeon->enemy_forces_required,
                'warnings'         => $warningResult
            ];

            // Siege of Boralus faction but hide it otherwise
            if ($dungeonRoute->dungeon->isSiegeOfBoralus()) {
                $result['faction'] = $dungeonRoute->faction->name;
            }

            return $result;
        } catch (InvalidMDTString $ex) {
            return abort(400, __('The MDT string format was not recognized.'));
        } catch (Exception $ex) {
            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('Invalid MDT string: %s'), $ex->getMessage());
            } else {
                $message = __('Invalid MDT string');
            }

            report($ex);
            Log::error($ex->getMessage(), ['string' => $string]);
            return abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, 'MDT importer is not configured properly. Please contact the admin about this issue.');
            }

            throw $error;
        }
    }

    /**
     * @param ImportStringFormRequest $request
     * @param SeasonService $seasonService
     * @return Factory|View|void
     * @throws Exception
     * @throws Throwable
     */
    public function import(ImportStringFormRequest $request, SeasonService $seasonService)
    {
        $user = Auth::user();

        $sandbox = (bool)$request->get('mdt_import_sandbox', false);
        // @TODO This should be handled differently imho
        if ($sandbox || ($user !== null && $user->canCreateDungeonRoute())) {
            $string = $request->get('import_string');
            $importString = new ImportString($seasonService);

            try {
                $dungeonRoute = $importString->setEncodedString($string)->getDungeonRoute(collect(), $sandbox, true);

                // Keep track of the import
                $mdtImport = new MDTImport();
                $mdtImport->dungeon_route_id = $dungeonRoute->id;
                $mdtImport->import_string = $string;
                $mdtImport->save();
            } catch (InvalidMDTString $ex) {
                return abort(400, __('The MDT string format was not recognized.'));
            } catch (Exception $ex) {
                report($ex);

                // Makes it easier to debug
                if (env('APP_DEBUG')) {
                    throw $ex;
                } else {
                    Log::error($ex->getMessage(), ['string' => $string]);

                    return abort(400, sprintf(__('Invalid MDT string: %s'), $ex->getMessage()));
                }
            } catch (Throwable $error) {
                if ($error->getMessage() === "Class 'Lua' not found") {
                    return abort(500, 'MDT importer is not configured properly. Please contact the admin about this issue.');
                }

                throw $error;
            }

            $result = redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonRoute]);
        } else if ($user === null) {
            return abort(StatusCode::UNAUTHORIZED, 'You must be logged in to create a route');
        } else {
            $result = view('dungeonroute.limitreached');
        }

        return $result;
    }
}
