<?php

namespace App\Console\Commands\Spell;

use App\Models\Dungeon;
use App\Models\Spell\Spell;
use Illuminate\Console\Command;

class ExportCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spell:exportcsv {dungeon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracts all spells of a specific dungeon and outputs them to a csv file.';

    /**
     * Execute the console command.
     */
    public function handle(
    ): int {
        $dungeonKey = $this->argument('dungeon');
        // Cannot do ->with('npcs') here - it won't load the relationship properly due to orWhere(dungeon_id = -1)
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::with('spells')->where('key', $dungeonKey)->firstOrFail();

        $csvData = $dungeon->spells->map(function (Spell $spell) {
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
            sprintf('%s_spells.csv', $dungeonKey),
            ['id', 'name', 'dispel_type', 'schools', 'aura', 'wowhead_link'],
            $csvData
        );

        return 0;
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
