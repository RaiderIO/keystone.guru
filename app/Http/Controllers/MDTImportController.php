<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Requests\MDT\ImportStringFormRequest;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTString;
use App\Logic\MDT\IO\ImportString;
use App\Models\AffixGroup\AffixGroup;
use App\Models\MDTImport;
use App\Service\Season\SeasonService;
use Exception;
use Illuminate\Contracts\View\Factory;
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
            $warnings     = new Collection();
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
                'dungeon'          => $dungeonRoute->dungeon !== null ? __($dungeonRoute->dungeon->name) : __('controller.mdtimport.unknown_dungeon'),
                'affixes'          => $affixes,
                'pulls'            => $dungeonRoute->killzones->count(),
                'paths'            => $dungeonRoute->paths->count(),
                'lines'            => $dungeonRoute->brushlines->count(),
                'notes'            => $dungeonRoute->mapicons->count(),
                'enemy_forces'     => $dungeonRoute->enemy_forces ?? 0,
                'enemy_forces_max' => $dungeonRoute->teeming ? $dungeonRoute->dungeon->enemy_forces_required_teeming : $dungeonRoute->dungeon->enemy_forces_required,
                'warnings'         => $warningResult,
            ];

            // Siege of Boralus faction but hide it otherwise
            if ($dungeonRoute->dungeon->isSiegeOfBoralus()) {
                $result['faction'] = $dungeonRoute->faction->name;
            }

            return $result;
        } catch (InvalidMDTString $ex) {
            return abort(400, __('controller.mdtimport.error.mdt_string_format_not_recognized'));
        } catch (Exception $ex) {
            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('controller.mdtimport.error.invalid_mdt_string_exception'), $ex->getMessage());
            } else {
                $message = __('controller.admintools.error.invalid_mdt_string');
            }

            // We're not interested if the string was 100% not an MDT string - it will never work then
            if (isValidBase64($string)) {
                report($ex);
            }

            Log::error($ex->getMessage(), ['string' => $string]);
            return abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, __('controller.mdtimport.error.mdt_importer_not_configured_properly'));
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
            $string       = $request->get('import_string');
            $importString = new ImportString($seasonService);

            try {
                $dungeonRoute = $importString->setEncodedString($string)->getDungeonRoute(collect(), $sandbox, true);

                // Ensure team_id is set
                if (!$sandbox) {
                    $dungeonRoute->team_id = $request->get('team_id', -1);
                    $dungeonRoute->save();
                }

                // Keep track of the import
                $mdtImport                   = new MDTImport();
                $mdtImport->dungeon_route_id = $dungeonRoute->id;
                $mdtImport->import_string    = $string;
                $mdtImport->save();
            } catch (InvalidMDTString $ex) {
                return abort(400, __('controller.mdtimport.error.mdt_string_format_not_recognized'));
            } catch (Exception $ex) {
                // We're not interested if the string was 100% not an MDT string - it will never work then
                if (isValidBase64($string)) {
                    report($ex);
                }

                // Makes it easier to debug
                if (config('app.debug')) {
                    throw $ex;
                } else {
                    Log::error($ex->getMessage(), ['string' => $string]);

                    return abort(400, sprintf(__('controller.mdtimport.error.invalid_mdt_string_exception'), $ex->getMessage()));
                }
            } catch (Throwable $error) {
                if ($error->getMessage() === "Class 'Lua' not found") {
                    return abort(500, __('controller.mdtimport.error.mdt_importer_not_configured_properly'));
                }

                throw $error;
            }

            $result = redirect()->route('dungeonroute.edit', ['dungeonroute' => $dungeonRoute]);
        } else if ($user === null) {
            return abort(StatusCode::UNAUTHORIZED, __('controller.mdtimport.error.cannot_create_route_must_be_logged_in'));
        } else {
            $result = view('dungeonroute.limitreached');
        }

        return $result;
    }
}
