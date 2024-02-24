<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\NpcFormRequest;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Models\NpcBolsteringWhitelist;
use App\Models\NpcClassification;
use App\Models\NpcSpell;
use App\Models\Spell;
use App\Service\Npc\NpcServiceInterface;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;

class NpcController extends Controller
{
    use ChangesMapping;

    /**
     * Checks if the incoming request is a save as new request or not.
     *
     *
     * @return bool
     */
    private function isSaveAsNew(Request $request)
    {
        return $request->get('submit', 'submit') !== 'Submit';
    }

    /**
     * @param Npc|null $npc
     *
     * @return array|mixed
     * @throws Exception
     */
    public function store(NpcFormRequest $request, Npc $npc = null)
    {
        $oldId        = null;
        $oldDungeonId = null;
        // If we're saving as new, make a new NPC and save that instead
        if ($npc === null || $this->isSaveAsNew($request)) {
            $npc = new Npc();
        } else {
            $oldId        = $npc->id;
            $oldDungeonId = $npc->dungeon_id;
        }

        $npcBefore = clone $npc;

        $validated  = $request->validated();
        $attributes = [
            'id'                => $validated['id'],
            'dungeon_id'        => $validated['dungeon_id'],
            'classification_id' => $validated['classification_id'],
            'npc_type_id'       => $validated['npc_type_id'],
            'npc_class_id'      => $validated['npc_class_id'],
            'name'              => $validated['name'],
            // Remove commas or dots in the name; we want the integer value
            'base_health'       => str_replace([',', '.'], '', (string) $validated['base_health']),
            'health_percentage' => $validated['health_percentage'],
            'aggressiveness'    => $validated['aggressiveness'],
            'dangerous'         => $validated['dangerous'] ?? 0,
            'truesight'         => $validated['truesight'] ?? 0,
            'bursting'          => $validated['bursting'] ?? 0,
            'bolstering'        => $validated['bolstering'] ?? 0,
            'sanguine'          => $validated['sanguine'] ?? 0,
            'runs_away_in_fear' => $validated['runs_away_in_fear'] ?? 0,
        ];

        if ($oldId === null) {
            $attributes['display_id'] = null;
            $npc->setRawAttributes($attributes);
            $saveResult = $npc->save();
        } else {
            $saveResult = $npc->update($attributes);
        }

        if ($saveResult) {
            // Bolstering whitelist, if set
            $bolsteringWhitelistNpcs = $validated['bolstering_whitelist_npcs'] ?? [];
            // Clear current whitelists
            $npc->npcbolsteringwhitelists()->delete();
            foreach ($bolsteringWhitelistNpcs as $whitelistNpcId) {
                NpcBolsteringWhitelist::insert([
                    'npc_id'           => $npc->id,
                    'whitelist_npc_id' => $whitelistNpcId,
                ]);
            }

            // Spells, if set
            $spells = $validated['spells'] ?? [];
            // Clear current spells
            $npc->npcspells()->delete();
            foreach ($spells as $spellId) {
                NpcSpell::insert([
                    'npc_id'   => $npc->id,
                    'spell_id' => $spellId,
                ]);
            }


            $existingEnemyForces = 0;

            // Now create new enemy forces. Default to 0, but can be set if we just changed the dungeon
            if ($oldId === null) {
                $npc->createNpcEnemyForcesForExistingMappingVersions($existingEnemyForces);
            } else {
                Enemy::where('npc_id', $oldId)->update(['npc_id' => $npc->id]);
                NpcEnemyForces::where('npc_id', $oldId)->update(['npc_id' => $npc->id]);

                $changes = $npc->getChanges();

                // If we changed the dungeon our enemy forces no longer match up with the mapping version, so get rid of them
                // But we can keep them if the dungeon is now the generic dungeon, then all mapping versions are valid
                if (isset($changes['dungeon_id']) && $changes['dungeon_id'] !== -1) {
                    // Change all existing enemy forces for all older mapping versions
                    $currentDungeonMappingVersionId = Dungeon::findOrFail($oldDungeonId)->currentMappingVersion->id;

                    $npc->npcEnemyForces()
                        ->where('mapping_version_id', '!=', $currentDungeonMappingVersionId)
                        ->delete();

                    // Update the latest mapping version enemy forces to the new latest mapping version
                    $npc->npcEnemyForces()
                        ->where('mapping_version_id', $currentDungeonMappingVersionId)
                        ->update([
                            'mapping_version_id' => Dungeon::findOrFail($changes['dungeon_id'])->currentMappingVersion->id,
                        ]);
                }
            }

            // Broadcast notifications so that any open mapping sessions get these changes immediately
            // If no dungeon is set, user selected 'All Dungeons'
            $npcAllDungeon       = ($npc->dungeon === null);
            $npcBeforeAllDungeon = ($npcBefore->dungeon === null);

            // Prevent sending multiple messages for the same dungeon
            $messagesSentToDungeons = collect();
            if ($npcAllDungeon || $npcBeforeAllDungeon) {
                // Broadcast the event for all dungeons
                foreach (Dungeon::all() as $dungeon) {
                    if ($npc->dungeon === null && $messagesSentToDungeons->search($dungeon->id) === false) {
                        broadcast(new ModelChangedEvent($dungeon, Auth::user(), $npc));
                        $messagesSentToDungeons->push($dungeon->id);
                    }
                    if ($npcBefore->dungeon === null && $messagesSentToDungeons->search($dungeon->id) === false) {
                        broadcast(new ModelChangedEvent($dungeon, Auth::user(), $npcBefore));
                        $messagesSentToDungeons->push($dungeon->id);
                    }
                }
            }

            // If we're now for all dungeons we don't have a current dungeon so we can't do these checks
            // We already sent messages above in this case so we're already good
            if (!$npcAllDungeon) {
                // Let previous dungeon know that this NPC is no longer available
                if ($messagesSentToDungeons->search($npc->dungeon->id) === false) {
                    broadcast(new ModelChangedEvent($npc->dungeon, Auth::user(), $npc));
                    $messagesSentToDungeons->push($npc->dungeon->id);
                }
                if (!$npcBeforeAllDungeon && $messagesSentToDungeons->search($npc->dungeon->id) === false) {
                    broadcast(new ModelChangedEvent($npcBefore->dungeon, Auth::user(), $npc));
                    $messagesSentToDungeons->push($npc->dungeon->id);
                }
            }

            // Re-load the relations so we're echoing back a fully updated npc
            $npc->load(['npcbolsteringwhitelists', 'spells']);

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($npcBefore, $npc);
        } // We got to update any existing enemies with the old ID to the new ID, makes it easier to convert ids
        else {
            abort(500, 'Unable to save npc!');
        }

        return $npc;
    }

    /**
     * Show a page for creating a new npc.
     *
     * @return Factory|View
     */
    public function new()
    {
        return view('admin.npc.edit', [
            'classifications' => NpcClassification::all()->pluck('name', 'id')->mapWithKeys(fn(string $name, int $id) => [$id => __($name)]),
            'spells'          => Spell::all(),
            'bolsteringNpcs'  =>
                Npc::orderByRaw('dungeon_id, name')
                    ->get()
                    ->groupBy('dungeon_id')
                    ->mapWithKeys(function ($value, $key) {
                        // Halls of Valor => [npcs]
                        $dungeonName = $key === -1 ? __('views/admin.npc.edit.all_dungeons') : __(Dungeon::find($key)->name);

                        return [
                            $dungeonName => $value->pluck('name', 'id')
                                ->map(fn($value, $key) => // Make sure the value is formatted as 'Hymdal (123456)'
                                sprintf('%s (%s)', $value, $key)),
                        ];
                    })
                    ->toArray(),
        ]);
    }

    /**
     *
     * @return Factory|View
     */
    public function edit(Request $request, NpcServiceInterface $npcService, Npc $npc)
    {
        return view('admin.npc.edit', [
            'npc'             => $npc,
            'classifications' => NpcClassification::all()->pluck('name', 'id')->mapWithKeys(fn(string $name, int $id) => [$id => __($name)]),
            'spells'          => Spell::all(),
            'bolsteringNpcs'  => $npc->dungeon === null ? [] : $npcService->getNpcsForDropdown($npc->dungeon, true),
        ]);
    }

    /**
     * Override to give the type hint which is required.
     *
     *
     * @return Factory|RedirectResponse|View
     * @throws Exception
     */
    public function update(NpcFormRequest $request, NpcServiceInterface $npcService, Npc $npc)
    {
        if ($this->isSaveAsNew($request)) {
            return $this->savenew($request);
        } else {
            // Store it and show the edit page again
            $npc = $this->store($request, $npc);

            // Message to the user
            Session::flash('status', __('views/admin.npc.flash.npc_updated'));

            // Display the edit page
            return $this->edit($request, $npcService, $npc);
        }
    }

    /**
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function savenew(NpcFormRequest $request)
    {
        // Store it and show the edit page
        $npc = $this->store($request);

        // Message to the user
        Session::flash('status', sprintf(__('views/admin.npc.flash.npc_created'), $npc->name));

        return redirect()->route('admin.npc.edit', ['npc' => $npc->id]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function list()
    {
        return view('admin.npc.list', ['models' => Npc::all()]);
    }
}
