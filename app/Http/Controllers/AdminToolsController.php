<?php

namespace App\Http\Controllers;

use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\IO\ImportString;
use App\Logic\MDT\IO\ImportWarning;
use App\Models\Dungeon;
use App\Models\Npc;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminToolsController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin.tools.list');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function mdtview()
    {
        return view('admin.tools.mdt.string');
    }

    /**
     * @param Request $request
     */
    public function mdtviewsubmit(Request $request)
    {
        $string = $request->get('import_string');
        $importString = new ImportString();
        echo json_encode($importString->setEncodedString($string)->getDecoded());
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function mdtdiff()
    {
        $warnings = new Collection();
        $npcs = Npc::all();

        // For each dungeon
        foreach (Dungeon::active()->get() as $dungeon) {
            $mdtNpcs = (new MDTDungeon($dungeon->name))->getMDTNPCs();

            // For each NPC that is found in the MDT Dungeon
            foreach ($mdtNpcs as $mdtNpc) {
                $mdtNpc = (object)$mdtNpc;
                // Find our own NPC
                /** @var Npc $npc */
                $npc = $npcs->where('id', $mdtNpc->id)->first();

                // Not found..
                if ($npc === null) {
                    $warnings->push(
                        new ImportWarning('missing_npc',
                            sprintf(__('Unable to find npc for id %s'), $mdtNpc->id),
                            ['mdt_npc' => $mdtNpc, 'npc' => $npc]
                        )
                    );
                } // Found, compare
                else {
                    // Match health
                    if ($npc->base_health !== (int)$mdtNpc->health) {
                        $warnings->push(
                            new ImportWarning('mismatched_health',
                                sprintf(__('NPC %s has mismatched health values, MDT: %s, KG: %s'), $mdtNpc->id, (int)$mdtNpc->health, $npc->base_health),
                                ['mdt_npc' => $mdtNpc, 'npc' => $npc, 'old' => $npc->base_health, 'new' => (int)$mdtNpc->health]
                            )
                        );
                    }

                    // Match enemy forces
                    if ($npc->enemy_forces !== (int)$mdtNpc->count) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_forces',
                                sprintf(__('NPC %s has mismatched enemy forces, MDT: %s, KG: %s'), $mdtNpc->id, (int)$mdtNpc->count, $npc->enemy_forces),
                                ['mdt_npc' => $mdtNpc, 'npc' => $npc, 'old' => $npc->enemy_forces, 'new' => (int)$mdtNpc->count]
                            )
                        );
                    }

                    // Match clone count, should be equal
                    if ($npc->enemies->count() !== count($mdtNpc->clones)) {
                        $warnings->push(
                            new ImportWarning('mismatched_enemy_count',
                                sprintf(__('NPC %s has mismatched enemy count, MDT: %s, KG: %s'),
                                    $mdtNpc->id, count($mdtNpc->clones), $npc->enemies === null ? 0 : $npc->enemies->count()),
                                ['mdt_npc' => $mdtNpc, 'npc' => $npc]
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
            default:
                abort(500, 'Invalid category');
                break;
        }

        // Whatever
        return [];
    }
}