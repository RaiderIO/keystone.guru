<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Requests\MDT\ImportStringFormRequest;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTString;
use App\Models\AffixGroup\AffixGroup;
use App\Models\MDTImport;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\Season\SeasonServiceInterface;
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
     * @param MDTImportStringServiceInterface $mdtImportStringService
     * @param SeasonServiceInterface $seasonService
     * @return array|void
     * @throws Throwable
     */
    public function details(
        ImportStringFormRequest         $request,
        MDTImportStringServiceInterface $mdtImportStringService,
        SeasonServiceInterface          $seasonService
    )
    {
        $validated = $request->validated();
        $string    = $validated['import_string'];

//        try {
            $warnings     = new Collection();
            $mdtImportDetails = $mdtImportStringService
                ->setEncodedString($string)
                ->getDetails($warnings);

//            $result = [
//                'dungeon'                    => $dungeonRoute->dungeon !== null ? __($dungeonRoute->dungeon->name) : __('controller.mdtimport.unknown_dungeon'),
//                'affixes'                    => $affixes,
//                'pulls'                      => $dungeonRoute->killZones->count(),
//                'paths'                      => $dungeonRoute->paths->count(),
//                'lines'                      => $dungeonRoute->brushlines->count(),
//                'notes'                      => $dungeonRoute->mapicons->count(),
//                'enemy_forces'               => $dungeonRoute->enemy_forces ?? 0,
//                'enemy_forces_max'           => $dungeonRoute->teeming ? $dungeonRoute->mappingVersion->enemy_forces_required_teeming : $dungeonRoute->mappingVersion->enemy_forces_required,
//                'warnings'                   => $warningResult,
//            ];

//            // Siege of Boralus faction but hide it otherwise
//            if ($dungeonRoute->dungeon->isFactionSelectionRequired()) {
//                $result['faction'] = __($dungeonRoute->faction->name);
//            }

            return $mdtImportDetails;
//        } catch (InvalidMDTString $ex) {
//            return abort(400, __('controller.mdtimport.error.mdt_string_format_not_recognized'));
//        } catch (Exception $ex) {
//            // Different message based on our deployment settings
//            if (config('app.debug')) {
//                $message = sprintf(__('controller.mdtimport.error.invalid_mdt_string_exception'), $ex->getMessage());
//            } else {
//                $message = __('controller.admintools.error.invalid_mdt_string');
//            }
//
//            // We're not interested if the string was 100% not an MDT string - it will never work then
//            if (isValidBase64($string)) {
//                report($ex);
//            }
//
//            Log::error($ex->getMessage());
//            return abort(400, $message);
//        } catch (Throwable $error) {
//            if ($error->getMessage() === "Class 'Lua' not found") {
//                return abort(500, __('controller.mdtimport.error.mdt_importer_not_configured_properly'));
//            }
//            Log::error($error->getMessage());
//
//            throw $error;
//        }
    }

    /**
     * @param ImportStringFormRequest $request
     * @param MDTImportStringServiceInterface $mdtImportStringService
     * @return Factory|View|void
     * @throws Throwable
     */
    public function import(ImportStringFormRequest $request, MDTImportStringServiceInterface $mdtImportStringService)
    {
        $user = Auth::user();

        $validated = $request->validated();

        $sandbox = $validated['mdt_import_sandbox'] ?? false;
        // @TODO This should be handled differently imho
        if ($sandbox || ($user !== null && $user->canCreateDungeonRoute())) {
            $string = $validated['import_string'];

            try {
                $dungeonRoute = $mdtImportStringService
                    ->setEncodedString($string)
                    ->getDungeonRoute(collect(), $sandbox, true, $validated['import_as_this_week'] ?? false);

                // Ensure team_id is set
                if (!$sandbox) {
                    $dungeonRoute->team_id = $validated['team_id'] ?? null;
                    $dungeonRoute->save();
                }

                // Keep track of the import
                MDTImport::create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'import_string'    => $string,
                ]);
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
                    Log::error($ex->getMessage());

                    return abort(400, sprintf(__('controller.mdtimport.error.invalid_mdt_string_exception'), $ex->getMessage()));
                }
            } catch (Throwable $error) {
                if ($error->getMessage() === "Class 'Lua' not found") {
                    return abort(500, __('controller.mdtimport.error.mdt_importer_not_configured_properly'));
                }

                throw $error;
            }

            $result = redirect()->route('dungeonroute.edit', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute,
                'title'        => $dungeonRoute->getTitleSlug(),
            ]);
        } else if ($user === null) {
            return abort(StatusCode::UNAUTHORIZED, __('controller.mdtimport.error.cannot_create_route_must_be_logged_in'));
        } else {
            $result = view('dungeonroute.limitreached');
        }

        return $result;
    }
}
