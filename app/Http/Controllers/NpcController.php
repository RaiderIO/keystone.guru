<?php

namespace App\Http\Controllers;

use App\Events\Models\Npc\NpcChangedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\NpcFormRequest;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcBolsteringWhitelist;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\User;
use App\Service\Npc\NpcServiceInterface;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;

class NpcController extends Controller
{
    use ChangesMapping;

    /**
     * Checks if the incoming request is a save as new request or not.
     */
    private function isSaveAsNew(Request $request): bool
    {
        return $request->get('submit', 'submit') !== 'Submit';
    }

    /**
     * @return array|mixed
     *
     * @throws Exception
     */
    public function store(NpcFormRequest $request, ?Npc $npc = null)
    {
        $oldId         = null;
        $oldDungeonIds = null;
        // If we're saving as new, make a new NPC and save that instead
        if ($npc === null || $this->isSaveAsNew($request)) {
            $npc = new Npc();
        } else {
            $oldId         = $npc->id;
            $oldDungeonIds = $npc->dungeons->pluck('id')->toArray();
        }

        $npcBefore = clone $npc;

        $validated  = $request->validated();
        $attributes = [
            'id'                => $validated['id'],
            'classification_id' => $validated['classification_id'],
            'npc_type_id'       => $validated['npc_type_id'],
            'npc_class_id'      => $validated['npc_class_id'],
            'name'              => $validated['name'],
            'health_percentage' => (int)$validated['health_percentage'] === 100 ? null : $validated['health_percentage'],
            'level'             => $validated['level'],
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
            // Dungeons
            $dungeonIds = $validated['dungeon_ids'] ?? [];
            // Clear current whitelists
            $npc->npcDungeons()->delete();
            $dungeonAttributes = [];
            foreach ($dungeonIds as $dungeonId) {
                $dungeonAttributes[] = [
                    'npc_id'     => $npc->id,
                    'dungeon_id' => $dungeonId,
                ];
            }
            NpcDungeon::insert($dungeonAttributes);
            $npc->load('dungeons');

            // Bolstering whitelist, if set
            $bolsteringWhitelistNpcs = $validated['bolstering_whitelist_npcs'] ?? [];
            // Clear current whitelists
            $npc->npcbolsteringwhitelists()->delete();
            $bolsteringWhitelistNpcAttributes = [];
            foreach ($bolsteringWhitelistNpcs as $whitelistNpcId) {
                $bolsteringWhitelistNpcAttributes[] = [
                    'npc_id'           => $npc->id,
                    'whitelist_npc_id' => $whitelistNpcId,
                ];
            }
            NpcBolsteringWhitelist::insert($bolsteringWhitelistNpcAttributes);

            // Spells, if set
            $spells = $validated['spells'] ?? [];
            // Clear current spells
            $npc->npcSpells()->delete();
            $npcSpellAttributes = [];
            foreach ($spells as $spellId) {
                $npcSpellAttributes[] = [
                    'npc_id'   => $npc->id,
                    'spell_id' => $spellId,
                ];
            }
            NpcSpell::insert($npcSpellAttributes);

            $existingEnemyForces = 0;
            // Now create new enemy forces. Default to 0, but can be set if we just changed the dungeon
            if ($oldId === null) {
                $npc->createNpcEnemyForcesForExistingMappingVersions($existingEnemyForces);
            } else {
                Enemy::where('npc_id', $oldId)->update(['npc_id' => $npc->id]);
                NpcEnemyForces::where('npc_id', $oldId)->update(['npc_id' => $npc->id]);

                // If we changed the dungeon our enemy forces no longer match up with the mapping version, so get rid of them
                // But we can keep them if the dungeon is now the generic dungeon, then all mapping versions are valid
                if (!empty(array_diff($oldDungeonIds, $npc->dungeons->pluck('id')->toArray()))) {
                    // Change all existing enemy forces for all older mapping versions
                    /** @var Collection<Dungeon> $dungeons */
                    $dungeons = Dungeon::whereIn('id', $oldDungeonIds)->get();
                    foreach ($dungeons as $dungeon) {
                        $currentDungeonMappingVersionId = $dungeon->currentMappingVersion->id;

                        $npc->npcEnemyForces()
                            ->where('mapping_version_id', '!=', $currentDungeonMappingVersionId)
                            ->delete();

                        // Update the latest mapping version enemy forces to the new latest mapping version
                        $npc->npcEnemyForces()
                            ->where('mapping_version_id', $currentDungeonMappingVersionId)
                            ->update([
                                'mapping_version_id' => $dungeon->currentMappingVersion->id,
                            ]);
                    }
                }
            }

            /** @var User $user */
            $user = Auth::user();
            foreach ($npc->dungeons as $dungeon) {
                broadcast(new NpcChangedEvent($dungeon, $user, $npc));
            }

            foreach ($npcBefore->dungeons as $dungeon) {
                broadcast(new NpcChangedEvent($dungeon, $user, $npcBefore));
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
    public function create()
    {
        return view('admin.npc.edit', [
            'classifications' => NpcClassification::all()->pluck('name', 'id')->mapWithKeys(static fn(string $name, int $id) => [$id => __($name)]),
            'spells'          => Spell::all(),
            'bolsteringNpcs'  => Npc::join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
                ->join('dungeons', 'npc_dungeons.dungeon_id', '=', 'dungeons.id')
                ->join('translations', static function (JoinClause $join) {
                    $join->on('translations.key', '=', 'npcs.name')
                        ->where('translations.locale', '=', 'en_US');
                })
                ->selectRaw('npcs.*, npc_dungeons.dungeon_id as dungeon_id')
                ->orderByRaw('npc_dungeons.dungeon_id, name')
                ->get()
                ->groupBy('dungeon_id')
                ->mapWithKeys(static function ($value, $key) {
                    // Halls of Valor => [npcs]
                    $dungeonName = __(Dungeon::find($key)?->name);

                    return [
                        $dungeonName => $value->pluck('name', 'id')
                            ->map(static fn($value, $key) => sprintf('%s (%s)', $value, $key)),
                    ];
                })
                ->toArray(),
        ]);
    }

    /**
     * @return Factory|View
     */
    public function edit(Request $request, NpcServiceInterface $npcService, Npc $npc): View
    {
        return view('admin.npc.edit', [
            'npc'             => $npc,
            'classifications' => NpcClassification::all()->pluck('name', 'id')->mapWithKeys(static fn(string $name, int $id) => [$id => __($name)]),
            'spells'          => Spell::all(),
            'bolsteringNpcs'  => $npcService->getNpcsForDropdown($npc->dungeons),
        ]);
    }

    /**
     * Override to give the type hint which is required.
     *
     *
     * @return Factory|RedirectResponse|View
     *
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
            Session::flash('status', __('view_admin.npc.flash.npc_updated'));

            // Display the edit page
            return $this->edit($request, $npcService, $npc);
        }
    }

    /**
     * @throws Exception
     */
    public function savenew(NpcFormRequest $request): RedirectResponse
    {
        // Store it and show the edit page
        $npc = $this->store($request);

        // Message to the user
        Session::flash('status', sprintf(__('view_admin.npc.flash.npc_created'), $npc->name));

        return redirect()->route('admin.npc.edit', ['npc' => $npc->id]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory|
     */
    public function get(): View
    {
        return view('admin.npc.list', ['models' => Npc::all()]);
    }
}
