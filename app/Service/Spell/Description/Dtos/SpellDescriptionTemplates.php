<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * What a game build's Spell table has to say about a set of spells.
 */
class SpellDescriptionTemplates
{
    /**
     * @param array<int, string> $described     spell id => description template, for every spell that has one
     * @param array<int, bool>   $presentIds    every spell id the build's Spell table holds a row for,
     *                                          with or without a description
     * @param array<int, bool>   $referencedIds every spell id those templates point at, whether or not
     *                                          that spell has a description of its own
     */
    public function __construct(
        public readonly array $described,
        public readonly array $presentIds,
        public readonly array $referencedIds,
    ) {
    }

    /**
     * Whether the build knows this spell at all. A spell it has never heard of is not a spell whose
     * description it can be trusted to have dropped.
     */
    public function isPresent(int $spellId): bool
    {
        return isset($this->presentIds[$spellId]);
    }

    /**
     * Every spell the rest of the DB2 tables must be read for: the ones we are rendering, plus the ones
     * their descriptions reference for a value or a name.
     *
     * @param  array<int, bool> $ourIds
     * @return array<int, bool>
     */
    public function getWantedIds(array $ourIds): array
    {
        return $ourIds + array_fill_keys(array_keys($this->described), true) + $this->referencedIds;
    }
}
