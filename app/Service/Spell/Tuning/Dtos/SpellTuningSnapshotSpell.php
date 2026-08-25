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
        public readonly string  $dispelType,
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
            dispelType: (string)($spell['dispel_type'] ?? ''),
        );
    }

    public function hasDescription(): bool
    {
        return $this->descriptionFormat !== null;
    }

    /**
     * A generic placeholder record (Blizzard's own template for a spell it never bothered fetching real
     * data for, e.g. a bare auto-attack) rather than a real, player-visible spell. `icon_name` and
     * `description_values` alone are not enough - real boss abilities exist with no icon and a purely
     * static (numberless) description (e.g. spells 153954, 246943) - but those still carry a genuine
     * `dispel_type` ("n/a" is itself a real, fetched value). A placeholder's `dispel_type` was never
     * populated at all. A "tuning change" on a placeholder is data noise, not something a player would
     * recognise.
     */
    public function isPlaceholder(): bool
    {
        return $this->values === [] && $this->iconName === '' && $this->dispelType === '';
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
