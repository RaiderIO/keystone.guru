<?php

namespace Tests\Feature\Traits;

use DOMDocument;
use DOMElement;
use DOMXPath;

trait ReadsDungeonSelect
{
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
