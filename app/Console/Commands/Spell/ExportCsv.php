<?php

namespace App\Console\Commands\Spell;

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Console\Command;
use Str;

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
    public function handle(): int
    {
        $dungeonKey = $this->argument('dungeon');
        // Cannot do ->with('npcs') here - it won't load the relationship properly due to orWhere(dungeon_id = -1)
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::with('spells')->where('key', $dungeonKey)->firstOrFail();

        $csvData = $dungeon->spells->where('hidden_on_map', 0)->map(function (Spell $spell) {
            /** @var Npc|null $npc */
            $npc = Npc::with('npcSpells')->whereRelation('npcSpells', 'spell_id', $spell->id)->first();

            return [
                'id'           => $spell->id,
                'npc_id'       => optional($npc)->id ?? 'UNKNOWN',
                'mechanic'     => __($spell->mechanic, [], 'en_US'),
                'name'         => __($spell->name, [], 'en_US'),
                'dispel_type'  => $spell->dispel_type,
                'schools'      => Spell::maskToReadableString(Spell::ALL_SCHOOLS, $spell->schools_mask),
                'miss_types'   => Spell::maskToReadableString(Spell::ALL_MISS_TYPES, $spell->miss_types_mask),
                'aura'         => $spell->aura ? 1 : 0,
                'debuff'       => $spell->debuff ? 1 : 0,
                'cast_time'    => $spell->cast_time,
                'duration'     => $spell->duration,
                'wowhead_link' => $spell->getWowheadLink(),
            ];
        })->toArray();

        $this->outputToCsv(
            sprintf('%s_spells.csv', Str::slug(__($dungeon->name, [], 'en_US'))),
            ['id', 'npc_id', 'mechanic', 'name', 'dispel_type', 'schools', 'miss_types', 'aura', 'debuff', 'cast_time', 'duration', 'wowhead_link'],
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
