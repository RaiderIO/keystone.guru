<?php

namespace App\Service\Spell\Tuning\Dtos;

use App\Models\Spell\SpellTuningChangeType;
use App\Service\Spell\Description\Dtos\SpellDescriptionValueKind;
use Illuminate\Support\Str;

/**
 * One change found between two snapshots, before it knows which builds or game version it belongs to.
 */
class SpellTuningChangeDto
{
    public function __construct(
        public readonly int                        $spellId,
        public readonly SpellTuningChangeType      $changeType,
        public readonly ?int                       $valueIndex,
        public readonly ?SpellDescriptionValueKind $kind,
        public readonly ?float                     $oldCoefficient,
        public readonly ?float                     $newCoefficient,
        public readonly ?string                    $oldText,
        public readonly ?string                    $newText,
        public readonly ?float                     $delta,
    ) {
    }

    public function isScalable(): bool
    {
        return $this->kind?->isScalable() ?? false;
    }

    /**
     * The row `spell_tuning_changes` stores for this change.
     *
     * @return array<string, mixed>
     */
    public function toRow(int $gameVersionId, string $fromBuild, string $toBuild): array
    {
        return [
            'game_version_id' => $gameVersionId,
            'spell_id'        => $this->spellId,
            'from_build'      => $fromBuild,
            'to_build'        => $toBuild,
            'to_build_number' => self::buildNumber($toBuild),
            'change_type'     => $this->changeType->value,
            'value_index'     => $this->valueIndex,
            'kind'            => $this->kind?->value,
            'old_coefficient' => $this->oldCoefficient,
            'new_coefficient' => $this->newCoefficient,
            'old_text'        => $this->oldText,
            'new_text'        => $this->newText,
            'delta'           => $this->delta,
        ];
    }

    /**
     * The last segment of a client build (`12.1.0.69404` -> 69404), which is what climbs between patches
     * and so what build history sorts on.
     */
    public static function buildNumber(string $build): int
    {
        return (int)Str::afterLast($build, '.');
    }
}
