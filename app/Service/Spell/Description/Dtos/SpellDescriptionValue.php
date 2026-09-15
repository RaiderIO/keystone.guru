<?php

namespace App\Service\Spell\Description\Dtos;

use Illuminate\Contracts\Support\Arrayable;

/**
 * One number in a rendered spell description, and what it took to work it out.
 *
 * A scalable value keeps the coefficient it came from, so the amount can be recalculated for a given
 * key level instead of being fixed at whatever it was when the description was imported.
 *
 * @implements Arrayable<string, mixed>
 */
class SpellDescriptionValue implements Arrayable
{
    public function __construct(
        public readonly SpellDescriptionValueKind $kind,
        public readonly string                    $text,
        public readonly ?float                    $coefficient = null,
        public readonly ?int                      $spellId = null,
        public readonly ?int                      $effectIndex = null,
    ) {
    }

    /**
     * @param array<string, mixed> $value
     */
    public static function fromArray(array $value): self
    {
        return new self(
            kind: SpellDescriptionValueKind::from($value['kind'] ?? SpellDescriptionValueKind::Value->value),
            text: (string)($value['text'] ?? ''),
            coefficient: isset($value['coefficient']) ? (float)$value['coefficient'] : null,
            spellId: isset($value['spellId']) ? (int)$value['spellId'] : null,
            effectIndex: isset($value['effectIndex']) ? (int)$value['effectIndex'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'kind'        => $this->kind->value,
            'text'        => $this->text,
            'coefficient' => $this->coefficient,
            'spellId'     => $this->spellId,
            'effectIndex' => $this->effectIndex,
        ], static fn(mixed $value): bool => $value !== null);
    }
}
