<?php

namespace App\Service\Spell\Tuning\Dtos;

use App\Service\Spell\Description\Dtos\RenderedSpellDescription;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;

/**
 * The tuning-relevant part of one spell at one build: the numbers in its description.
 */
class SpellTuningSnapshotSpell
{
    /**
     * @param array<int, SpellDescriptionValue> $values one per number in the description, in order
     */
    public function __construct(
        public readonly int     $id,
        public readonly int     $gameVersionId,
        public readonly ?string $descriptionFormat,
        public readonly array   $values,
        public readonly string  $iconName,
    ) {
    }

    /**
     * @param array<string, mixed> $spell one entry of spells.json, or a row of the spells table as an array
     */
    public static function fromArray(array $spell): self
    {
        $rawValues = $spell['description_values'] ?? null;
        if (is_string($rawValues)) {
            $rawValues = json_decode($rawValues, true);
        }

        return new self(
            id: (int)$spell['id'],
            gameVersionId: (int)$spell['game_version_id'],
            descriptionFormat: isset($spell['description_format']) ? (string)$spell['description_format'] : null,
            values: array_values(array_map(
                static fn(array $value): SpellDescriptionValue => SpellDescriptionValue::fromArray($value),
                is_array($rawValues) ? $rawValues : [],
            )),
            iconName: (string)($spell['icon_name'] ?? ''),
        );
    }

    public function hasDescription(): bool
    {
        return $this->descriptionFormat !== null;
    }

    /**
     * A generic placeholder record (Blizzard's own template for a spell it never named, e.g. a bare
     * auto-attack) rather than a real, player-visible spell: no numbers to tune and no icon to show it
     * with. A "tuning change" on one is data noise, not something a player would recognise.
     */
    public function isPlaceholder(): bool
    {
        return $this->values === [] && $this->iconName === '';
    }

    /**
     * The description as it read at this build, or null when the spell had none.
     */
    public function render(): ?string
    {
        if ($this->descriptionFormat === null) {
            return null;
        }

        return new RenderedSpellDescription($this->descriptionFormat, $this->values)->render();
    }

    /**
     * The sequence of value kinds, which is what decides whether two builds' values can be compared
     * position by position.
     *
     * @return array<int, string>
     */
    public function getKindSequence(): array
    {
        return array_map(static fn(SpellDescriptionValue $value): string => $value->kind->value, $this->values);
    }
}
