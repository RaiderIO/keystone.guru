<?php

namespace App\Console\Commands\CombatLog;

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
            $this->info(sprintf('Dungeon %d', $dungeonId));
            /** @var Collection $spellsByDungeon */
            $spellIds = $spellsByDungeon->keys();

            foreach ($spellIds as $spellId) {
                if ($allSpells->has($spellId)) {
                    $spellsToOutput->put($spellId, $allSpells->get($spellId));
                    continue;
                }

                $createdSpell = $this->createSpellAndFetchInfo($wowheadService, $spellId);

                if ($createdSpell instanceof Spell) {
                    $allSpells->push($createdSpell);
                    $spellsToOutput->put($spellId, $createdSpell);

                    $this->info(sprintf('- Created spell %s (%d)', $createdSpell->name, $spellId));
                }
            }
        }

        $csvData = $spellsToOutput->map(function (Spell $spell) {
            return [
                'id'           => $spell->id,
                'name'         => $spell->name,
                'dispel_type'  => $spell->dispel_type,
                'schools'      => Spell::maskToReadableString($spell->schools_mask),
                'wowhead_link' => $spell->getWowheadLink(),
            ];
        })->toArray();

        $this->outputToCsv(
            str_replace(['.txt', '.zip'], '_spells.csv', $filePath),
            ['id', 'name', 'dispel_type', 'schools', 'wowhead_link'],
            $csvData
        );

        return 0;
    }

    private function createSpellAndFetchInfo(WowheadServiceInterface $wowheadService, int $spellId): ?Spell
    {
        $spellDataResult = $wowheadService->getSpellData($spellId);

        return Spell::create([
            'id'           => $spellId,
            'icon_name'    => $spellDataResult->getIconName(),
            'name'         => $spellDataResult->getName(),
            'dispel_type'  => $spellDataResult->getDispelType(),
            'schools_mask' => $spellDataResult->getSchoolsMask(),
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
