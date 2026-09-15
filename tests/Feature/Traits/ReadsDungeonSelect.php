<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

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
     *
     * Fails loudly - naming the options the page actually rendered - when there is no selection to read,
     * rather than returning null. A test asserting a specific id used to get `Failed asserting that null
     * is identical to 3`, which is true both when the select is missing entirely and when the picked
     * dungeon simply is not one the filter offers (see dungeonsOfferedByDungeonSelect()). Those are very
     * different bugs, and a mapping reimport keeps producing the second one. Use assertNoDungeonSelected()
     * for the path that expects no selection on purpose.
     *
     * The offered ids are read off the rendered HTML rather than recomputed with
     * dungeonsOfferedByDungeonSelect(): the rendered list is the evidence, the query is only the test's
     * model of it, and a disagreement between the two is precisely what needs to stay visible.
     */
    protected function getSelectedDungeonId(string $html, string $selectId = 'compendium_filter_dungeon'): int
    {
        ['selected' => $selected, 'offered' => $offered] = $this->readDungeonSelect($html, $selectId);

        if ($selected === []) {
            throw new RuntimeException(sprintf(
                'The #%s select renders no selected option. It offers %d option(s): [%s]. A dungeon the filter ' .
                'does not offer never renders as selected - pick from dungeonsOfferedByDungeonSelect().',
                $selectId,
                count($offered),
                implode(', ', $offered),
            ));
        }

        if (count($selected) > 1) {
            throw new RuntimeException(sprintf(
                'The #%s select renders %d selected options ([%s]), so there is no single selection to read.',
                $selectId,
                count($selected),
                implode(', ', $selected),
            ));
        }

        $value = $selected[0];

        // The select also carries non-dungeon options (-1 for "all", `season-<id>`, `expansion-<id>`), and
        // casting one of those to int silently yields 0 or some unrelated id
        if (!ctype_digit($value)) {
            throw new RuntimeException(sprintf(
                'The #%s select has "%s" selected, which is not a dungeon id.',
                $selectId,
                $value,
            ));
        }

        return (int)$value;
    }

    /**
     * Asserts that the dungeon filter rendered, but with nothing selected - the documented behaviour for a
     * dungeon the filter does not offer.
     *
     * Stricter than the `assertNotSame($dungeon->id, $this->getSelectedDungeonId(...))` it replaces, which
     * also passed when some *other* dungeon was selected and could not tell a missing select from an
     * unselected one.
     */
    protected function assertNoDungeonSelected(string $html, string $selectId = 'compendium_filter_dungeon'): void
    {
        ['selected' => $selected, 'offered' => $offered] = $this->readDungeonSelect($html, $selectId);

        $this->assertSame([], $selected, sprintf(
            'Expected the #%s select to render no selected option, but [%s] is selected out of %d offered option(s).',
            $selectId,
            implode(', ', $selected),
            count($offered),
        ));
    }

    /**
     * The dungeon ids the filter rendered as options, in document order.
     *
     * The select also carries `all`, season and expansion options; those are not dungeon ids and are left
     * out. Throws when the select is not on the page at all.
     *
     * @return list<int>
     */
    protected function getOfferedDungeonIds(string $html, string $selectId = 'compendium_filter_dungeon'): array
    {
        $offered = $this->readDungeonSelect($html, $selectId)['offered'];

        return array_values(array_map(
            static fn(string $value) => (int)$value,
            array_filter($offered, static fn(string $value) => ctype_digit($value) && (int)$value > 0),
        ));
    }

    /**
     * Reads the dungeon filter out of a rendered page.
     *
     * Throws when the select is not on the page at all: no caller wants that outcome, and every one of them
     * used to read it as "nothing is selected".
     *
     * @return array{selected: list<string>, offered: list<string>}
     */
    private function readDungeonSelect(string $html, string $selectId): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        $selects = $xpath->query(sprintf('//select[@id="%s"]', $selectId));

        if ($selects === false || $selects->length === 0) {
            throw new RuntimeException(sprintf(
                'The rendered page has no <select id="%s">. Selects present: [%s].',
                $selectId,
                implode(', ', $this->attributeValues($xpath->query('//select[@id]'), 'id')),
            ));
        }

        return [
            'selected' => $this->attributeValues($xpath->query(sprintf('//select[@id="%s"]//option[@selected]', $selectId)), 'value'),
            'offered'  => $this->attributeValues($xpath->query(sprintf('//select[@id="%s"]//option', $selectId)), 'value'),
        ];
    }

    /**
     * @param  DOMNodeList<DOMNode>|false $nodes
     * @return list<string>
     */
    private function attributeValues(DOMNodeList|false $nodes, string $attribute): array
    {
        if ($nodes === false) {
            return [];
        }

        $values = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $values[] = $node->getAttribute($attribute);
            }
        }

        return $values;
    }
}
