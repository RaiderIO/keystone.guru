<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Builder;

trait ReadsDungeonSelect
{
    /**
     * The dungeons that common/dungeon/select offers as options: an active dungeon of an active expansion,
     * mapped for the visitor's game version. A dungeon outside that set renders no selected option at all,
     * so a test that needs to observe the selection must pick from here rather than from Dungeon::active(),
     * which happily hands back a legion-remix-only dungeon the retail filter never lists.
     *
     * @return Builder<Dungeon>
     */
    protected function dungeonsOfferedByDungeonSelect(): Builder
    {
        return Dungeon::active()
            ->whereHas('expansion', static fn(Builder $query) => $query->where('active', 1))
            ->whereHas(
                'mappingVersions',
                static fn(Builder $query) => $query->where('game_version_id', GameVersion::getUserOrDefaultGameVersion()->id),
            );
    }

    /**
     * The dungeon id that a rendered page's dungeon filter is pre-selected with.
     */
    protected function getSelectedDungeonId(string $html, string $selectId = 'compendium_filter_dungeon'): ?int
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $options = new DOMXPath($dom)->query(sprintf('//select[@id="%s"]//option[@selected]', $selectId));

        if ($options === false) {
            return null;
        }

        $option = $options->item(0);

        return $option instanceof DOMElement ? (int)$option->getAttribute('value') : null;
    }
}
