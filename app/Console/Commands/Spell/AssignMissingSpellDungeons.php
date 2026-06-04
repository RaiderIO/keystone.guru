<?php

namespace App\Console\Commands\Spell;

use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AssignMissingSpellDungeons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spell:assignmissingdungeons {npc? : The NPC ID to assign missing spell dungeons for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns a dungeon to spells that are missing one but are used by npcs that have a dungeon assigned.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $bar = $this->output->createProgressBar(Npc::count());
        $bar->start();

        $npcId = $this->argument('npc');

        Npc::with(['npcDungeons', 'npcSpells.spell.spellDungeons'])
            ->when($npcId !== null, fn($q) => $q->where('id', $npcId))
            ->chunk(100, function (Collection $npcs) use ($bar) {
                foreach ($npcs as $npc) {
                    /** @var Npc $npc */
                    // No dungeons assigned, cannot infer anything
                    if ($npc->npcDungeons->count() === 0) {
                        continue;
                    }

                    // For each spell this NPC can cast, ensure that that spell is also assigned to the same dungeon(s)
                    $npc->npcSpells->each(function (NpcSpell $npcSpell) use ($npc) {
                        /** @var Spell $spell */
                        $spell = $npcSpell->spell;

                        // For each dungeon this NPC is in, ensure the spell is also assigned to that dungeon
                        foreach ($npc->npcDungeons as $npcDungeon) {
                            if ($spell->spellDungeons->pluck('dungeon_id')->contains($npcDungeon->dungeon_id)) {
                                continue;
                            }

                            SpellDungeon::firstOrCreate([
                                'spell_id'   => $spell->id,
                                'dungeon_id' => $npcDungeon->dungeon_id,
                            ]);
                        }
                    });
                }

                $bar->advance($npcs->count());
            });

        $bar->finish();
        $this->newLine();

        return 0;
    }
}
