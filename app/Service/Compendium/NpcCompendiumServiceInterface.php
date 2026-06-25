<?php

namespace App\Service\Compendium;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface NpcCompendiumServiceInterface
{
    /**
     * Build the merged, sorted event feed for the NPC compendium detail page.
     * Combines CombatLogNpcEvents and CombatLogSpellEvents, sorted by created_at descending.
     *
     * @return Collection<int, CombatLogNpcEvent|CombatLogSpellEvent>
     */
    public function buildEventFeed(Npc $npc): Collection;

    /**
     * Get paginated list of distinct dates that have activity events for the given dungeon, sorted descending.
     *
     * @return LengthAwarePaginator<int, string>
     */
    public function getActivityDates(int $perPage = 10, ?Dungeon $dungeon = null): LengthAwarePaginator;

    /**
     * Get all events (NPC + Spell) for a specific calendar day and dungeon, sorted by created_at descending.
     *
     * @return Collection<int, CombatLogNpcEvent|CombatLogSpellEvent>
     */
    public function getEventsForDate(Carbon $date, ?Dungeon $dungeon = null): Collection;
}
