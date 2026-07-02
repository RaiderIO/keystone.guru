<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcType;
use App\Models\Spell\Spell;
use App\Service\Mapping\MappingExportServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminToolsNpcController extends Controller
{
    use ChangesMapping;

    public function npcimport(): View
    {
        return view('admin.tools.npc.import');
    }

    public function npcimportsubmit(Request $request): void
    {
        $importString = $request->get('import_string');

        // Correct the string since wowhead sucks
        $importString = str_replace('[Listview.extraCols.popularity]', '["Listview.extraCols.popularity"]', (string)$importString);

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
            // 12 => NpcType::BATTLE_PET,
        ];

        $aggressivenessMapping = [
            -1 => 'aggressive',
            0  => 'neutral',
            1  => 'friendly',
        ];

        $classificationMapping = [
            0 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            1 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL],
            2 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE],
            3 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
            4 => NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE],
        ];

        try {
            foreach ($decoded['data'] as $npcData) {
                $npcCandidate = Npc::findOrNew($npcData['id']);

                $beforeModel = clone $npcCandidate;

                /** @var Dungeon $dungeon */
                $dungeons = Dungeon::whereIn('zone_id', $npcData['location'])->get();
                if ($dungeons->isEmpty()) {
                    $log[] = sprintf('*** Unable to find dungeon(s) with zone_id(s) %s; npc %s (%s) NOT added; needs manual work', implode(', ', $npcData['location']), $npcData['id'], $npcData['name']);

                    continue;
                }

                if (!isset($npcTypeMapping[$npcData['type']])) {
                    $log[] = sprintf('*** Unable to find npc type %s; npc %s (%s) NOT added; needs manual work', $npcData['type'], $npcData['id'], $npcData['name']);

                    continue;
                }

                $npcCandidate->id                = $npcData['id'];
                $npcCandidate->classification_id = $classificationMapping[($npcData['classification'] ?? 0) + ($npcData['boss'] ?? 0) + 1];
                // Bosses
                if ($npcCandidate->classification_id >= NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]) {
                    $npcCandidate->dangerous = true;
                }

                $npcCandidate->npc_type_id = $npcTypeMapping[$npcData['type']];
                // This will be converted to a translation with localization:exportnpcnames!
                $npcCandidate->name = $npcData['name'];

                $npcCandidate->aggressiveness = isset($npcData['react']) && is_array($npcData['react']) ? $aggressivenessMapping[$npcData['react'][0] ?? -1] : 'aggressive';

                $existed = $npcCandidate->exists;
                if ($npcCandidate->save()) {
                    foreach ($dungeons as $dungeon) {
                        NpcDungeon::create([
                            'npc_id'     => $npcCandidate->id,
                            'dungeon_id' => $dungeon->id,
                        ]);

                        if ($existed) {
                            $log[] = sprintf('Updated NPC %s (%s) in %s', $npcData['id'], $npcData['name'], __($dungeon->name));
                        } else {
                            // Now create new enemy forces. Default to 0
                            $npcCandidate->createNpcEnemyForcesForExistingMappingVersions();

                            $log[] = sprintf('Inserted NPC %s (%s) in %s', $npcData['id'], $npcData['name'], __($dungeon->name));
                        }
                    }
                } else {
                    $log[] = sprintf('*** Error saving NPC %s (%s)', $npcData['id'], $npcData['name']);
                }

                // Changed the mapping; so make sure we synchronize it
                $this->mappingChanged($beforeModel, $npcCandidate);
            }

            // Cannot do this automatically due to permission issues
            $log[] = '';
            $log[] = 'You can now run php artisan localization:exportnpcnames to export the NPC names to the localization files.';
            $log[] = 'You can now run php artisan localization:importnpcnames to convert the NPC names to translation keys in the database.';
        } catch (Exception $exception) {
            dump($exception);
        } finally {
            dump($log);
        }
    }

    public function manageSpellVisibility(Request $request, ?Dungeon $dungeon = null): View
    {
        return view('admin.tools.npc.managespellvisibility', [
            'npcs' => Npc::when($dungeon !== null, fn(Builder $builder) => $builder->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
                ->select('npcs.*')
                ->where('npc_dungeons.dungeon_id', $dungeon->id))->with('npcSpells')
                ->has('npcSpells')
                ->paginate(50),
            'spells' => Spell::with('gameVersion')
                ->when(
                    $dungeon !== null,
                    fn(Builder $builder) => $builder->whereRelation('spellDungeons', 'dungeon_id', $dungeon->id),
                )
                ->get()
                ->keyBy('id'),
            'dungeon' => $dungeon,
        ]);
    }

    public function manageSpellVisibilitySubmit(Request $request): RedirectResponse
    {
        $dungeonId = (int)$request->get('dungeon_id');
        $dungeon   = null;
        if ($dungeonId !== -1) {
            $dungeon = Dungeon::findOrFail($dungeonId);
        }

        return redirect()->route('admin.tools.npc.managespellvisibility', ['dungeon' => $dungeon]);
    }

    public function npcsShowMissingDisplayId(): View
    {
        return view('admin.tools.npc.showmissingdisplayid', [
            'npcs' => Npc::whereNull('display_id')->get(),
        ]);
    }

    public function npcsSaveToSeeder(MappingExportServiceInterface $mappingExportService): Response
    {
        return response(json_encode($mappingExportService->serializeNpcs(), JSON_PRETTY_PRINT), 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="npcs.json"',
        ]);
    }
}
