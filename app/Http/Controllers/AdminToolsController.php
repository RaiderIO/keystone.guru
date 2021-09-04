<?php /** @noinspection PhpVoidFunctionResultUsedInspection */

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ChangesMapping;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\Exception\InvalidMDTString;
use App\Logic\MDT\IO\ExportString;
use App\Logic\MDT\IO\ImportString;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Npc;
use App\Models\NpcType;
use App\Service\Cache\CacheService;
use App\Service\Season\SeasonService;
use App\Traits\SavesArrayToJsonFile;
use Artisan;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Session;
use Throwable;

class AdminToolsController extends Controller
{
    use ChangesMapping;
    use SavesArrayToJsonFile;

    /**
     * @return Factory|View
     */
    public function index()
    {
        return view('admin.tools.list');
    }

    /**
     * @return Application|Factory|View
     */
    public function npcimport()
    {
        return view('admin.tools.npcimport.import');
    }

    /**
     * @param Request $request
     * @return void
     */
    public function npcimportsubmit(Request $request)
    {
        $importString = $request->get('import_string');

        // Correct the string since wowhead sucks
        $importString = str_replace('[Listview.extraCols.popularity]', '["Listview.extraCols.popularity"]', $importString);

        $decoded = json_decode($importString, true);

        $log = [];

        // Wowhead type => keystone.guru type
        $npcTypeMapping = [
            15 => NpcType::ABERRATION,
            1  => NpcType::BEAST,
            8  => NpcType::CRITTER,
            3  => NpcType::DEMON,
            2  => NpcType::DRAGONKIN,
            4  => NpcType::ELEMENTAL,
            5  => NpcType::GIANT,
            7  => NpcType::HUMANOID,
            9  => NpcType::MECHANICAL,
            6  => NpcType::UNDEAD,
            10 => NpcType::UNCATEGORIZED,
        ];

        $aggressivenessMapping = [
            -1 => 'aggressive',
            0  => 'neutral',
            1  => 'friendly',
        ];

        try {
            foreach ($decoded['data'] as $npcData) {
                $npcCandidate = Npc::findOrNew($npcData['id']);

                $beforeModel = clone $npcCandidate;

                /** @var Dungeon $dungeon */
                $dungeon = Dungeon::where('zone_id', $npcData['location'][0])->first();
                if ($dungeon === null) {
                    $log[] = sprintf('*** Unable to find dungeon with zone_id %s; npc %s (%s) NOT added; needs manual work', $npcData['location'][0], $npcData['id'], $npcData['name']);
                    continue;
                }

                if ($npcCandidate->exists) {
                    $log[] = sprintf('Updated NPC %s (%s) in %s', $npcData['id'], $npcData['name'], __($dungeon->name));
                } else {
                    $log[] = sprintf('Inserted NPC %s (%s) in %s', $npcData['id'], $npcData['name'], __($dungeon->name));
                }

                $npcCandidate->id                = $npcData['id'];
                $npcCandidate->classification_id = ($npcData['classification'] ?? 0) + ($npcData['boss'] ?? 0) + 1;
                $npcCandidate->npc_type_id       = $npcTypeMapping[$npcData['type']];
                // 8 since we start the expansion with 8 dungeons usually
                $npcCandidate->dungeon_id = count($npcData['location']) >= 8 ? -1 : $dungeon->id;
                $npcCandidate->name       = $npcData['name'];
                // Do not overwrite health if it was set already
                if ($npcCandidate->base_health <= 0) {
                    $npcCandidate->base_health = 12345;
                }
                $npcCandidate->aggressiveness = isset($npcData['react']) && is_array($npcData['react']) ? $aggressivenessMapping[$npcData['react'][0] ?? -1] : 'aggressive';

                $npcCandidate->save();

                // Changed the mapping; so make sure we synchronize it
                $this->mappingChanged($beforeModel->exists ? $beforeModel : null, $npcCandidate);
            }
        } catch (Exception $ex) {

            dump($ex);
        } finally {
            dump($log);
        }
    }

    /**
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonroute()
    {
        return view('admin.tools.dungeonroute.view');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function dungeonroutesubmit(Request $request)
    {
        $dungeonRoute = DungeonRoute::with([
            'faction', 'specializations', 'classes', 'races', 'affixes',
            'brushlines', 'paths', 'author', 'killzones', 'pridefulenemies', 'publishedstate',
            'ratings', 'favorites', 'enemyraidmarkers', 'mapicons', 'mdtImport', 'team',
        ])->where('public_key', $request->get('public_key'))->firstOrFail();

        return view('admin.tools.dungeonroute.viewcontents', [
            'dungeonroute' => $dungeonRoute,
        ]);
    }

    /**
     * @return Factory|
     */
    public function mdtview()
    {
        return view('admin.tools.mdt.string');
    }

    /**
     * @param Request $request
     * @param SeasonService $seasonService
     * @return JsonResponse
     */
    public function mdtviewsubmit(Request $request, SeasonService $seasonService)
    {
        return response()->json((new ImportString($seasonService))
            ->setEncodedString($request->get('import_string'))
            ->getDecoded());
    }

    /**
     * @return Factory|
     */
    public function mdtviewasdungeonroute()
    {
        return view('admin.tools.mdt.string', ['asDungeonroute' => true]);
    }

    /**
     * @param Request $request
     * @param SeasonService $seasonService
     *
     * @throws Exception
     * @throws Throwable
     */
    public function mdtviewasdungeonroutesubmit(Request $request, SeasonService $seasonService)
    {
        try {
            $dungeonRoute = (new ImportString($seasonService))
                ->setEncodedString($request->get('import_string'))
                ->getDungeonRoute(new Collection(), false, false);
            $dungeonRoute->makeVisible(['killzones']);

            dd($dungeonRoute);
        } catch (InvalidMDTString $ex) {
            return abort(400, __('controller.admintools.error.mdt_string_format_not_recognized'));
        } catch (Exception $ex) {

            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('controller.admintools.error.invalid_mdt_string_exception'), $ex->getMessage());
            } else {
                $message = __('controller.admintools.error.invalid_mdt_string');
            }
            return abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, __('controller.admintools.error.mdt_importer_not_configured'));
            }

            throw $error;
        }
    }

    /**
     * @return Factory|
     */
    public function mdtviewasstring()
    {
        return view('admin.tools.mdt.dungeonroute');
    }

    /**
     * @param Request $request
     * @param SeasonService $seasonService
     *
     * @throws Exception
     * @throws Throwable
     */
    public function mdtviewasstringsubmit(Request $request, SeasonService $seasonService)
    {
        $dungeonRoute = DungeonRoute::where('public_key', $request->get('public_key'))->firstOrFail();

        try {
            $warnings = new Collection();

            $exportString = (new ExportString($seasonService))
                ->setDungeonRoute($dungeonRoute)
                ->getEncodedString($warnings);

            $stringContents = (new ImportString($seasonService))
                ->setEncodedString($exportString)
                ->getDecoded();

            dd($exportString, $stringContents);
        } catch (Exception $ex) {

            // Different message based on our deployment settings
            if (config('app.debug')) {
                $message = sprintf(__('controller.admintools.error.invalid_mdt_string_exception'), $ex->getMessage());
            } else {
                $message = __('controller.admintools.error.invalid_mdt_string');
            }
            return abort(400, $message);
        } catch (Throwable $error) {
            if ($error->getMessage() === "Class 'Lua' not found") {
                return abort(500, __('controller.admintools.error.mdt_importer_not_configured'));
            }

            throw $error;
        }
    }


    /**
     * @return Factory|
     */
    public function mdtdiff()
    {
        $warnings = new Collection();
        $npcs     = Npc::with(['enemies', 'type'])->get();

        // For each dungeon
        foreach (Dungeon::active()->get() as $dungeon) {
            $mdtNpcs = (new MDTDungeon(__($dungeon->name, [], 'en')))->getMDTNPCs();

            // For each NPC that is found in the MDT Dungeon
            foreach ($mdtNpcs as $mdtNpc) {
                // Ignore mobs we should ignore
                if (!$mdtNpc->isValid() || $mdtNpc->isAwakened()) {
                    continue;
                }

                // Find our own NPC
                /** @var Npc $npc */
                $npc = $npcs->where('id', $mdtNpc->getId())->first();

                // Not found..
                if ($npc === null) {
                    $warnings->push(
                        new ImportWarning('missing_npc',
                            sprintf(__('controller.admintools.error.mdt_unable_to_find_npc_for_id'), $mdtNpc->getId()),
                            ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc]
                        )
                    );
                } // Found, compare
                else {

                    // Match health
                    if ($npc->base_health !== $mdtNpc->getHealth()) {
                        $warnings->push(
                            new ImportWarning('mismatched_health',
                                sprintf(__('controller.admintools.error.mdt_mismatched_health'), $mdtNpc->getId(), $mdtNpc->getHealth(), $npc->base_health),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->base_health, 'new' => $mdtNpc->getHealth()]
                            )
                        );
                    }

                    // Match enemy forces
                    if ($npc->enemy_forces !== $mdtNpc->getCount()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces'), $mdtNpc->getId(), $mdtNpc->getCount(), $npc->enemy_forces),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->enemy_forces, 'new' => $mdtNpc->getCount()]
                            )
                        );
                    }

                    // Match enemy forces teeming
                    if ($npc->enemy_forces_teeming !== $mdtNpc->getCountTeeming()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces_teeming',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_forces_teeming'), $mdtNpc->getId(), $mdtNpc->getCountTeeming(), $npc->enemy_forces_teeming),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->enemy_forces_teeming, 'new' => $mdtNpc->getCountTeeming()]
                            )
                        );
                    }

                    // Match clone count, should be equal
                    if ($npc->enemies->count() !== count($mdtNpc->getClones())) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_count',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_count'),
                                    $mdtNpc->getId(), count($mdtNpc->getClones()), $npc->enemies === null ? 0 : $npc->enemies->count()),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc]
                            )
                        );
                    }

                    // Match npc type, should be equal
                    if ($npc->type->type !== $mdtNpc->getCreatureType()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_type',
                                sprintf(__('controller.admintools.error.mdt_mismatched_enemy_type'),
                                    $mdtNpc->getId(), $mdtNpc->getCreatureType(), $npc->type->type),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->type->type, 'new' => $mdtNpc->getCreatureType()]
                            )
                        );
                    }
                }
            }
        }

        return view('admin.tools.mdt.diff', ['warnings' => $warnings]);
    }

    /**
     * @param Request $request
     * @param CacheService $cacheService
     * @return RedirectResponse
     */
    public function dropCache(Request $request, CacheService $cacheService)
    {
        $cacheService->dropCaches();

        Artisan::call('modelCache:clear');

        Session::flash('status', __('controller.admintools.flash.caches_dropped_successfully'));

        return redirect()->route('admin.tools');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function applychange(Request $request)
    {
        $category = $request->get('category');
        $npcId    = $request->get('npc_id');
        $value    = $request->get('value');

        /** @var Npc $npc */
        $npc = Npc::find($npcId);

        switch ($category) {
            case 'mismatched_health':
                $npc->base_health = $value;
                $npc->save();
                break;
            case 'mismatched_enemy_forces':
                $npc->enemy_forces = $value;
                $npc->save();
                break;
            case 'mismatched_enemy_forces_teeming':
                $npc->enemy_forces_teeming = $value;
                $npc->save();
                break;
            case 'mismatched_enemy_type':
                $npcType          = NpcType::where('type', $value)->first();
                $npc->npc_type_id = $npcType->id;
                $npc->save();
                break;
            default:
                abort(500, __('controller.admintools.error.mdt_invalid_category'));
                break;
        }

        // Whatever
        return [];
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function exportreleases(Request $request)
    {
        Artisan::call('release:save');

        Session::flash('status', __('controller.admintools.flash.releases_exported'));

        return view('admin.tools.list');
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws Exception
     */
    public function exportdungeondata(Request $request)
    {
        Artisan::call('mapping:save');

        return view('admin.tools.datadump.viewexporteddungeondata');
    }

    /**
     * @param Request $request
     * @return Application|Factory|\Illuminate\Contracts\View\View
     */
    public function exceptionselect(Request $request)
    {
        return view('admin.tools.exception.select');
    }

    /**
     * @param Request $request
     * @throws TokenMismatchException
     */
    public function exceptionselectsubmit(Request $request)
    {
        switch ($request->get('exception')) {
            case 'TokenMismatchException':
                throw new TokenMismatchException(__('controller.admintools.flash.exception.token_mismatch'));
            case 'InternalServerError':
                throw new Exception(__('controller.admintools.flash.exception.internal_server_error'));
        }
    }
}
