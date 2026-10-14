<?php

namespace App\Http\Controllers;

use App\Events\Models\Npc\NpcChangedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\NpcFormRequest;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcBolsteringWhitelistRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcClassificationRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcDungeonRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcEnemyForcesRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcSpellRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\Npc\NpcServiceInterface;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function store(
        NpcFormRequest                            $request,
        DungeonRepositoryInterface                $dungeonRepository,
        EnemyRepositoryInterface                  $enemyRepository,
        NpcBolsteringWhitelistRepositoryInterface $npcBolsteringWhitelistRepository,
        NpcDungeonRepositoryInterface             $npcDungeonRepository,
        NpcEnemyForcesRepositoryInterface         $npcEnemyForcesRepository,
        NpcSpellRepositoryInterface               $npcSpellRepository,
        ?Npc                                      $npc = null,
    ) {
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
            'game_version_id'   => $validated['game_version_id'],
            'classification_id' => $validated['classification_id'],
            'npc_type_id'       => $validated['npc_type_id'],
            'npc_class_id'      => $validated['npc_class_id'],
            'name'              => $validated['name'],
            'level'             => $validated['level'],
            'aggressiveness'    => $validated['aggressiveness'],
            'dangerous'         => $validated['dangerous'] ?? 0,
            'truesight'         => $validated['truesight'] ?? 0,
            'bursting'          => $validated['bursting'] ?? 0,
            'bolstering'        => $validated['bolstering'] ?? 0,
            'sanguine'          => $validated['sanguine'] ?? 0,
            'runs_away_in_fear' => $validated['runs_away_in_fear'] ?? 0,
        ];

        DB::transaction(function () use ($dungeonRepository, $enemyRepository, $npcBolsteringWhitelistRepository, $npcDungeonRepository, $npcEnemyForcesRepository, $npcSpellRepository, $npc, $npcBefore, $oldId, $oldDungeonIds, $attributes, $validated) {
            if ($oldId === null) {
                $attributes['display_id'] = null;
                $npc->setRawAttributes($attributes);
                $saveResult = $npc->save();
            } else {
                $saveResult = $npc->update($attributes);
            }

            if (!$saveResult) {
                abort(500, 'Unable to save npc!');
            }

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
            $npcDungeonRepository->insert($dungeonAttributes);
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
            $npcBolsteringWhitelistRepository->insert($bolsteringWhitelistNpcAttributes);

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
            $npcSpellRepository->insert($npcSpellAttributes);

            $existingEnemyForces = 0;
            // Now create new enemy forces. Default to 0, but can be set if we just changed the dungeon
            if ($oldId === null) {
                $npc->createNpcEnemyForcesForExistingMappingVersions($existingEnemyForces);
            } else {
                // We got to update any existing enemies with the old ID to the new ID, makes it easier to convert ids
                $enemyRepository->updateNpcId($oldId, $npc->id);
                $npcEnemyForcesRepository->updateNpcId($oldId, $npc->id);

                // If we changed the dungeon our enemy forces no longer match up with the mapping version, so get rid of them
                // But we can keep them if the dungeon is now the generic dungeon, then all mapping versions are valid
                $removedDungeonIds = array_diff($oldDungeonIds, $npc->dungeons->pluck('id')->toArray());
                if (!empty($removedDungeonIds)) {
                    /** @var Collection<int, Dungeon> $removedDungeons */
                    $removedDungeons = $dungeonRepository->getByIds($removedDungeonIds);
                    // For each removed dungeon, delete the enemy forces for all mapping versions
                    // Any new dungeons assigned will have to have their enemy forces created again
                    foreach ($removedDungeons as $removedDungeon) {
                        $npc->npcEnemyForces()
                            ->whereIn('mapping_version_id', $removedDungeon->mappingVersions->pluck('id')->toArray())
                            ->delete();
                    }
                }
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($npcBefore, $npc);
        });

        // Re-load the relations so we're echoing/broadcasting back a fully updated npc - the front-end
        // re-assigns enemy npcs from these payloads and reads these relations in the visuals/tooltips
        $npcRelationsToEcho = [
            'type',
            'class',
            'npcbolsteringwhitelists',
            'npcHealths',
            'spells',
        ];
        $npc->load($npcRelationsToEcho);

        /** @var User $user */
        $user = Auth::user();
        // If the npc was just renamed to a new id, tell listening clients so they can find enemies
        // still holding the old id in their in-memory state (the DB rows were already remapped)
        $oldNpcId = $oldId !== null && $oldId !== $npc->id ? $oldId : null;

        foreach ($npc->dungeons as $dungeon) {
            try {
                broadcast(new NpcChangedEvent($dungeon, $user, $npc, oldNpcId: $oldNpcId));
            } catch (BroadcastException) {
                // Ignore broadcast failures
            }
        }

        // Notify only the dungeons this npc was actually unassigned from during this edit - not
        // determined by reloading $npcBefore's relations, since that reflects the post-commit
        // (i.e. new) dungeon set rather than the pre-edit one
        if ($oldDungeonIds !== null) {
            $removedDungeonIds = array_diff($oldDungeonIds, $npc->dungeons->pluck('id')->toArray());
            if (!empty($removedDungeonIds)) {
                foreach ($dungeonRepository->getByIds($removedDungeonIds) as $dungeon) {
                    try {
                        broadcast(new NpcChangedEvent($dungeon, $user, $npc, removedFromDungeon: true, oldNpcId: $oldNpcId));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }
            }
        }

        return $npc;
    }

    /**
     * Show a page for creating a new npc.
     *
     * @return View
     */
    public function create(
        NpcRepositoryInterface               $npcRepository,
        DungeonRepositoryInterface           $dungeonRepository,
        NpcClassificationRepositoryInterface $npcClassificationRepository,
        SpellRepositoryInterface             $spellRepository,
    ) {
        return view('admin.npc.edit', [
            'classifications' => $npcClassificationRepository->all()->pluck('name', 'id')->mapWithKeys(static fn(
                string $name,
                int    $id,
            ) => [$id => __($name)]),
            'spells'         => $spellRepository->all(),
            'bolsteringNpcs' => $npcRepository->getForBolsteringWhitelistDropdown()
                ->groupBy('dungeon_id')
                ->mapWithKeys(static function ($value, $key) use ($dungeonRepository) {
                    // Halls of Valor => [npcs]
                    $dungeonName = __($dungeonRepository->find($key)?->name);

                    return [
                        $dungeonName => $value->pluck('name', 'id')
                            ->map(static fn($value, $key) => sprintf('%s (%s)', $value, $key)),
                    ];
                })
                ->toArray(),
        ]);
    }

    /**
     * @return View
     */
    public function edit(
        Request                              $request,
        NpcServiceInterface                  $npcService,
        NpcClassificationRepositoryInterface $npcClassificationRepository,
        SpellRepositoryInterface             $spellRepository,
        Npc                                  $npc,
    ): View {
        return view('admin.npc.edit', [
            'npc'             => $npc,
            'classifications' => $npcClassificationRepository->all()->pluck('name', 'id')->mapWithKeys(static fn(
                string $name,
                int    $id,
            ) => [$id => __($name)]),
            'spells'         => $spellRepository->all(),
            'bolsteringNpcs' => $npcService->getNpcsForDropdown($npc->dungeons),
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
    public function update(
        NpcFormRequest                            $request,
        NpcServiceInterface                       $npcService,
        DungeonRepositoryInterface                $dungeonRepository,
        EnemyRepositoryInterface                  $enemyRepository,
        NpcBolsteringWhitelistRepositoryInterface $npcBolsteringWhitelistRepository,
        NpcClassificationRepositoryInterface      $npcClassificationRepository,
        NpcDungeonRepositoryInterface             $npcDungeonRepository,
        NpcEnemyForcesRepositoryInterface         $npcEnemyForcesRepository,
        NpcSpellRepositoryInterface               $npcSpellRepository,
        SpellRepositoryInterface                  $spellRepository,
        Npc                                       $npc,
    ) {
        if ($this->isSaveAsNew($request)) {
            return $this->savenew($request, $dungeonRepository, $enemyRepository, $npcBolsteringWhitelistRepository, $npcDungeonRepository, $npcEnemyForcesRepository, $npcSpellRepository);
        } else {
            // Store it and show the edit page again
            $npc = $this->store($request, $dungeonRepository, $enemyRepository, $npcBolsteringWhitelistRepository, $npcDungeonRepository, $npcEnemyForcesRepository, $npcSpellRepository, $npc);

            // Message to the user
            Session::flash('status', __('view_admin.npc.flash.npc_updated'));

            // Display the edit page
            return $this->edit($request, $npcService, $npcClassificationRepository, $spellRepository, $npc);
        }
    }

    /**
     * @throws Exception
     */
    public function savenew(
        NpcFormRequest                            $request,
        DungeonRepositoryInterface                $dungeonRepository,
        EnemyRepositoryInterface                  $enemyRepository,
        NpcBolsteringWhitelistRepositoryInterface $npcBolsteringWhitelistRepository,
        NpcDungeonRepositoryInterface             $npcDungeonRepository,
        NpcEnemyForcesRepositoryInterface         $npcEnemyForcesRepository,
        NpcSpellRepositoryInterface               $npcSpellRepository,
    ): RedirectResponse {
        // Store it and show the edit page
        $npc = $this->store($request, $dungeonRepository, $enemyRepository, $npcBolsteringWhitelistRepository, $npcDungeonRepository, $npcEnemyForcesRepository, $npcSpellRepository);

        // Message to the user
        Session::flash('status', sprintf(__('view_admin.npc.flash.npc_created'), __($npc->name)));

        return redirect()->route('admin.npc.edit', ['npc' => $npc->id]);
    }

    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return View
     */
    public function get(NpcRepositoryInterface $npcRepository): View
    {
        return view('admin.npc.list', ['models' => $npcRepository->all()]);
    }
}
