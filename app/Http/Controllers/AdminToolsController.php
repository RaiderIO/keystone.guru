<?php

namespace App\Http\Controllers;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\IO\ImportString;
use App\Logic\MDT\IO\ImportWarning;
use App\Models\Dungeon;
use App\Models\Npc;
use App\Models\NpcType;
use App\Models\Release;
use App\Service\Season\SeasonService;
use App\Traits\SavesArrayToJsonFile;
use Artisan;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class AdminToolsController extends Controller
{
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
     * @return Application|Factory|View
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
            1  => 'friendly'
        ];

        try {
            foreach ($decoded['data'] as $npcData) {
                $npcCandidate = Npc::findOrNew($npcData['id']);

                /** @var Dungeon $dungeon */
                $dungeon = Dungeon::where('zone_id', $npcData['location'][0])->first();
                if ($dungeon === null) {
                    $log[] = sprintf('*** Unable to find dungeon with zone_id %s; npc %s (%s) NOT added; needs manual work', $npcData['location'][0], $npcData['id'], $npcData['name']);
                    continue;
                }

                if ($npcCandidate->exists) {
                    $log[] = sprintf('Updated NPC %s (%s) in %s', $npcData['id'], $npcData['name'], $dungeon->name);
                } else {
                    $log[] = sprintf('Inserted NPC %s (%s) in %s', $npcData['id'], $npcData['name'], $dungeon->name);
                }

                $npcCandidate->id = $npcData['id'];
                $npcCandidate->classification_id = ($npcData['classification'] ?? 0) + ($npcData['boss'] ?? 0) + 1;
                $npcCandidate->npc_type_id = $npcTypeMapping[$npcData['type']];
                // 8 since we start the expansion with 8 dungeons usually
                $npcCandidate->dungeon_id = count($npcData['location']) >= 8 ? -1 : $dungeon->id;
                $npcCandidate->name = $npcData['name'];
                // Do not overwrite health if it was set already
                if ($npcCandidate->base_health <= 0) {
                    $npcCandidate->base_health = 12345;
                }
                $npcCandidate->aggressiveness = isset($npcData['react']) && is_array($npcData['react']) ? $aggressivenessMapping[$npcData['react'][0] ?? -1] : 'aggressive';

                $npcCandidate->save();
            }
        } catch (Exception $ex) {

            dump($ex);
        } finally {
            dump($log);
        }
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
        return view('admin.tools.mdt.string', ['dungeonroute' => true]);
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
     * @return Factory|
     */
    public function mdtdiff()
    {
        $warnings = new Collection();
        $npcs = Npc::with(['enemies', 'type'])->get();

        // For each dungeon
        foreach (Dungeon::active()->get() as $dungeon) {
            $mdtNpcs = (new MDTDungeon($dungeon->name))->getMDTNPCs();

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
                            sprintf(__('Unable to find npc for id %s'), $mdtNpc->getId()),
                            ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc]
                        )
                    );
                } // Found, compare
                else {

                    // Match health
                    if ($npc->base_health !== $mdtNpc->getHealth()) {
                        $warnings->push(
                            new ImportWarning('mismatched_health',
                                sprintf(__('NPC %s has mismatched health values, MDT: %s, KG: %s'), $mdtNpc->getId(), $mdtNpc->getHealth(), $npc->base_health),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->base_health, 'new' => $mdtNpc->getHealth()]
                            )
                        );
                    }

                    // Match enemy forces
                    if ($npc->enemy_forces !== $mdtNpc->getCount()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces',
                                sprintf(__('NPC %s has mismatched enemy forces, MDT: %s, KG: %s'), $mdtNpc->getId(), $mdtNpc->getCount(), $npc->enemy_forces),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->enemy_forces, 'new' => $mdtNpc->getCount()]
                            )
                        );
                    }

                    // Match enemy forces teeming
                    if ($npc->enemy_forces_teeming !== $mdtNpc->getCountTeeming()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces_teeming',
                                sprintf(__('NPC %s has mismatched enemy forces teeming, MDT: %s, KG: %s'), $mdtNpc->getId(), $mdtNpc->getCountTeeming(), $npc->enemy_forces_teeming),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc, 'old' => $npc->enemy_forces_teeming, 'new' => $mdtNpc->getCountTeeming()]
                            )
                        );
                    }

                    // Match clone count, should be equal
                    if ($npc->enemies->count() !== count($mdtNpc->getClones())) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_count',
                                sprintf(__('NPC %s has mismatched enemy count, MDT: %s, KG: %s'),
                                    $mdtNpc->getId(), count($mdtNpc->getClones()), $npc->enemies === null ? 0 : $npc->enemies->count()),
                                ['mdt_npc' => (object)$mdtNpc->getRawMdtNpc(), 'npc' => $npc]
                            )
                        );
                    }

                    // Match npc type, should be equal
                    if ($npc->type->type !== $mdtNpc->getCreatureType()) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_type',
                                sprintf(__('NPC %s has mismatched enemy type, MDT: %s, KG: %s'),
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
     * @return array
     */
    public function applychange(Request $request)
    {
        $category = $request->get('category');
        $npcId = $request->get('npc_id');
        $value = $request->get('value');

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
                $npcType = NpcType::where('type', $value)->first();
                $npc->npc_type_id = $npcType->id;
                $npc->save();
                break;
            default:
                abort(500, 'Invalid category');
                break;
        }

        // Whatever
        return [];
    }

    /**
     * @param Request $request
     */
    public function exportreleases(Request $request)
    {
        $result = [];

        foreach (Release::all() as $release) {
            $releaseArr = $release->toArray();

            /** @var $release Release */
            $rootDirPath = database_path('/seeds/releases/');
            $this->saveDataToJsonFile($releaseArr, $rootDirPath, sprintf('%s.json', $release->version));

            $result[] = $releaseArr;
        }

        dd($result);
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
}