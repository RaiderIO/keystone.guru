<?php

namespace App\Service\MDT\Import\Mapping;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Npc\NpcHealth;
use App\Models\Npc\NpcType;
use App\Service\MDT\Logging\MDTMappingImportServiceLoggingInterface;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;

class NpcImporter
{
    public function __construct(
        private readonly MDTMappingImportServiceLoggingInterface $log,
    ) {
    }

    /**
     * @param array<int, Exception> $failures See MDTMappingImportServiceInterface::importNpcsDataFromMDT().
     */
    public function importNpcsDataFromMDT(MDTDungeon $mdtDungeon, Dungeon $dungeon, GameVersion $gameVersion, array &$failures = []): void
    {
        try {
            $this->log->importNpcsDataFromMDTStart($dungeon->key);

            // Get a list of NPCs and update/save them. npcHealths must be eager loaded here - it is read
            // per NPC below, which would otherwise be an N+1 that hard-fails
            // under preventLazyLoading (dev) for any dungeon that already has NPCs.
            $existingNpcs = $dungeon->npcs()->with(['npcHealths'])->get()->keyBy('id');

            $npcsUpdated           = $npcsInserted = 0;
            $npcDungeonsAttributes = [];

            foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
                if (in_array($mdtNpc->getId(), Npc::getCuratedDataNpcIds(), true)) {
                    $this->log->importNpcsDataFromMDTIgnoreNpc($mdtNpc->getId());
                    continue;
                }

                $npc = $existingNpcs->get($mdtNpc->getId());

                if ($newlyCreated = ($npc === null)) {
                    $npc = new Npc();
                    // Only on creation - an NPC that already exists may have had its game version
                    // corrected by hand, and a re-import of another game version must not undo that
                    $npc->game_version_id = $gameVersion->id;
                }

                $npc->id = $mdtNpc->getId();
                // Allow manual override to -1
                $npc->display_id   = $mdtNpc->getDisplayId();
                $npc->encounter_id = $mdtNpc->getEncounterId();
                $npc->classification_id ??= $mdtNpc->isBoss() ?
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS] :
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE];
                $npc->name        = $mdtNpc->getName();
                $npc->level       = $mdtNpc->getLevel();
                $npc->mdt_scale   = $mdtNpc->getScale();
                $npc->npc_type_id = NpcType::ALL[$mdtNpc->getCreatureType()] ?? NpcType::UNCATEGORIZED;
                $npc->truesight   = $mdtNpc->getStealthDetect();

                // If no dungeons are assigned, OR if the current dungeon is not part of the list
                if (!$npc->exists) {
                    $npc->load('npcDungeons');

                    if ($npc->npcDungeons->filter(
                        // Check if this NPC is already associated with this dungeon

                        fn(NpcDungeon $npcDungeon) => $npcDungeon->dungeon_id === $dungeon->id,
                    )->isEmpty()) {
                        $npcDungeonsAttributes[] = [
                            'npc_id'     => $npc->id,
                            'dungeon_id' => $dungeon->id,
                        ];
                    }
                }

                // MDT's health is never imported - it is frequently wrong (Midnight bosses stored ~4.17% high).
                // Health comes from combat logs (combatlog:extractnpchealth, which fills placeholder rows) or
                // by hand; a missing row gets the placeholder so the NPC has a row to fill.
                if ($npc->npcHealths->firstWhere('game_version_id', $gameVersion->id) === null) {
                    NpcHealth::query()->firstOrCreate([
                        'npc_id'          => $npc->id,
                        'game_version_id' => $gameVersion->id,
                    ], [
                        'health'     => NpcHealth::HEALTH_PLACEHOLDER,
                        'percentage' => null,
                    ]);
                }

                // Spells an MDT NPC lists are deliberately not imported (#3989): which spells an NPC casts
                // is derived exclusively from parsed combat log data (see NpcSpellAssignmentCollector),
                // which is curated data we gathered ourselves - MDT's list is not.

                try {
                    if ($newlyCreated) {
                        $saveResult = $npc->save();
                        $npcsInserted++;
                    } else {
                        $saveResult = $npc->update();
                        $npcsUpdated++;
                    }

                    if (!$saveResult) {
                        throw new Exception(sprintf('Unable to save npc %d!', $npc->id));
                    } elseif ($newlyCreated) {
                        $this->log->importNpcsDataFromMDTSaveNewNpc($npc->id);
                    }

                    if ($mdtNpc->getCount() > 0 && $newlyCreated) {
                        // For new NPCs go back and create enemy forces for all historical mapping versions
                        $npc->createNpcEnemyForcesForExistingMappingVersions($mdtNpc->getCount());
                    }
                } catch (UniqueConstraintViolationException) {
                    // Expected and recoverable, not a real failure: the NPC row already exists globally but
                    // is not linked to this dungeon yet, so the dungeon-scoped lookup above missed it and we
                    // attempted an insert on an existing primary key - the npc_dungeons link is written below,
                    // so the next run takes the update path.
                    $this->log->importNpcsDataFromMDTNpcNotMarkedForAllDungeons($npc->id);
                } catch (Exception $exception) {
                    $this->log->importNpcsDataFromMDTSaveNpcException($exception);
                    $failures[] = $exception;
                }
            }

            // Do not delete existing dungeons - we're only interested in new ones
            NpcDungeon::insert($npcDungeonsAttributes);

            $this->log->importNpcsDataFromMDTNpcsUpdate(
                $npcsUpdated,
                $npcsInserted,
                count($npcDungeonsAttributes),
            );
        } finally {
            $this->log->importNpcsDataFromMDTEnd();
        }
    }

    /**
     * Creates $newMappingVersion's enemy forces for every NPC of $dungeon that MDT reports a count for. Expects
     * importNpcsDataFromMDT() to have run first, so that newly added NPCs exist.
     *
     * @param  array<int, Exception> $failures Appended with NPC saves that failed - see
     *                                         importNpcsDataFromMDT()'s $failures parameter.
     * @throws Exception
     */
    public function importNpcEnemyForces(
        MappingVersion $newMappingVersion,
        MDTDungeon     $mdtDungeon,
        Dungeon        $dungeon,
        array          &          $failures,
    ): void {
        // Get a list of NPCs and update/save them (re-fetch the list to include any new NPCs)
        $npcs = $dungeon->npcs()->get()->keyBy('id');

        foreach ($mdtDungeon->getMDTNPCs() as $mdtNpc) {
            /** @var Npc|null $npc */
            $npc = $npcs->get($mdtNpc->getId());

            if ($npc === null) {
                $this->log->importNpcsUnableToFindNpc($mdtNpc->getId());

                continue;
            }

            try {
                if ($mdtNpc->getCount() > 0) {
                    // Create new enemy forces for this NPC for this new mapping version
                    NpcEnemyForces::create([
                        'mapping_version_id'   => $newMappingVersion->id,
                        'npc_id'               => $npc->id,
                        'enemy_forces'         => $mdtNpc->getCount(),
                        'enemy_forces_teeming' => null,
                    ]);

                    $this->log->importNpcsUpdateExistingNpc($npc->id);
                }

                // If shrouded (zul'gamux) update the mapping version to account for that
                if ($npc->isShrouded()) {
                    $newMappingVersion->update([
                        'enemy_forces_shrouded' => $mdtNpc->getCount(),
                    ]);
                } elseif ($npc->isShroudedZulGamux()) {
                    $newMappingVersion->update([
                        'enemy_forces_shrouded_zul_gamux' => $mdtNpc->getCount(),
                    ]);
                }
            } catch (Exception $exception) {
                $this->log->importNpcsDataFromMDTSaveNpcException($exception);
                $failures[] = $exception;
            }
        }
    }
}
