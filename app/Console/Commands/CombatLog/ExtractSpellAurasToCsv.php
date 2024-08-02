<?php

namespace App\Console\Commands\CombatLog;

use App\Logic\CombatLog\CombatEvents\CombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\Dungeon;
use App\Models\Spell;
use App\Service\CombatLog\CombatLogDataExtractionServiceInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Support\Collection;

class ExtractSpellAurasToCsv extends BaseCombatLogCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combatlog:extractspellaurastocsv {filePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracts all spells that enemies cast on players, saves them to database, fetches info from Wowhead and outputs to CSV.';

    /**
     * Execute the console command.
     */
    public function handle(
        CombatLogDataExtractionServiceInterface $combatLogDataExtractionService,
        WowheadServiceInterface                 $wowheadService
    ): int {
        $filePath = $this->argument('filePath');

        $allSpells = Spell::all()->keyBy('id');

        return $this->parseCombatLogRecursively(
            $filePath,
            fn(string $filePath) => $this->extractData(
                $combatLogDataExtractionService,
                $wowheadService,
                $filePath,
                $allSpells
            )
        );
    }

    /**
     * @param Collection<Spell> $allSpells
     */
    private function extractData(
        CombatLogDataExtractionServiceInterface $combatLogDataExtractionService,
        WowheadServiceInterface                 $wowheadService,
        string                                  $filePath,
        Collection                              $allSpells): int
    {
        $this->info(sprintf('Parsing file %s', $filePath));

        $result = $combatLogDataExtractionService->extractSpellAuraIds($filePath);

        $spellsToOutput = collect();

        foreach ($result as $dungeonId => $spellsByDungeon) {
            $this->info(sprintf('Dungeon %s', __(Dungeon::findOrFail($dungeonId)->name, [], 'en_US')));
            foreach ($spellsByDungeon as $spellId => $combatLogEvent) {
                /**
                 * @var int            $spellId
                 * @var CombatLogEvent $combatLogEvent
                 */
                if ($allSpells->has($spellId)) {
                    $spellsToOutput->put($spellId, $combatLogEvent);

                    // Update the spell based on a found combat log event
                    $this->updateSpell($allSpells->get($spellId), $combatLogEvent);
                    continue;
                }

                // Create a new spell and fetch the info for it
                $createdSpell = $this->createSpellAndFetchInfo($wowheadService, $spellId, $combatLogEvent);
                if ($createdSpell instanceof Spell) {
                    // Add to master list so that it doesn't get inserted twice
                    $allSpells->put($spellId, $createdSpell);
                    // Output this spell to CSV
                    $spellsToOutput->put($spellId, $combatLogEvent);

                    $this->info(sprintf('- Created spell %s (%d)', $createdSpell->name, $spellId));
                }
            }
        }

        $csvData = $spellsToOutput->map(function (CombatLogEvent $combatLogEvent, int $spellId) use ($allSpells) {
            /** @var Spell $spell */
            $spell = $allSpells->get($spellId);

            return [
                'id'           => $spell->id,
                'name'         => $spell->name,
                'dispel_type'  => $spell->dispel_type,
                'schools'      => Spell::maskToReadableString($spell->schools_mask),
                'aura'         => $spell->aura ? 1 : 0,
                'wowhead_link' => $spell->getWowheadLink(),
            ];
        })->toArray();

        $this->outputToCsv(
            str_replace(['.txt', '.zip'], '_spells.csv', $filePath),
            ['id', 'name', 'dispel_type', 'schools', 'aura', 'wowhead_link'],
            $csvData
        );

        return 0;
    }

    private function updateSpell(Spell $spell, CombatLogEvent $combatLogEvent): bool
    {
        // Update aura state
        return $spell->update([
            'aura' => $combatLogEvent->getGenericData()->getDestGuid() instanceof Creature && $combatLogEvent->getGenericData()->getDestGuid()->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE,
        ]);
    }

    private function createSpellAndFetchInfo(
        WowheadServiceInterface $wowheadService,
        int                     $spellId,
        CombatLogEvent          $combatLogEvent
    ): ?Spell {
        $spellDataResult = $wowheadService->getSpellData($spellId);

        $destGuid = $combatLogEvent->getGenericData()->getDestGuid();

        return Spell::create([
            'id'           => $spellId,
            'icon_name'    => $spellDataResult->getIconName(),
            'name'         => $spellDataResult->getName(),
            'dispel_type'  => $spellDataResult->getDispelType(),
            'schools_mask' => $spellDataResult->getSchoolsMask(),
            // Only when the target is a creature
            'aura'         => $destGuid instanceof Creature && $destGuid->getUnitType() === Creature::CREATURE_UNIT_TYPE_CREATURE,
        ]);
    }

    private function outputToCsv(string $filePath, array $headers, array $data): bool
    {
        $file = null;
        try {
            $file = fopen($filePath, 'w');
            fputcsv($file, $headers);
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        } finally {
            if ($file !== null) {
                fclose($file);

                $this->info(sprintf('- Created %s', $filePath));
            }
        }

        return true;
    }
}
