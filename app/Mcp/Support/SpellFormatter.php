<?php

namespace App\Mcp\Support;

use App\Models\CharacterClassSpecialization;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellSchool;

/**
 * Formats a spell for MCP tool output from what the spells table stores. Fields the table leaves empty, or only holds
 * as an "unknown" placeholder, are left out rather than guessed - except the cooldown fields, which are always present
 * and null when unknown, so that a missing cooldown is never read as "no cooldown".
 */
class SpellFormatter
{
    private const string UNKNOWN_SUFFIX = '.unknown';

    /**
     * The relations format() reads, to eager-load on the spells passed in.
     *
     * @var list<string>
     */
    public const array RELATIONS = ['descriptionTranslation', 'specializations.class'];

    /**
     * @return array<string, mixed>
     */
    public function format(Spell $spell): array
    {
        $cooldownMs = $spell->getEffectiveCooldownMs();

        return [...array_filter([
            'id'                 => $spell->id,
            'name'               => $spell->name === null ? null : __($spell->name),
            'description'        => $spell->description,
            'category'           => $this->knownOrNull($spell->category),
            'cooldown_group'     => $this->knownOrNull($spell->cooldown_group),
            'dispel_type'        => $this->knownOrNull($spell->dispel_type),
            'mechanic'           => $this->knownOrNull($spell->mechanic),
            'schools'            => $this->getSchools($spell->schools_mask),
            'cast_time_ms'       => $spell->cast_time ?: null,
            'duration_ms'        => $spell->duration ?: null,
            'aura'               => $spell->aura ?: null,
            'debuff'             => $spell->debuff ?: null,
            'is_pvp_talent'      => $spell->is_pvp_talent ?: null,
            'selectable_in_pull' => $spell->selectable ?: null,
        ], static fn($value) => $value !== null && $value !== [] && $value !== ''),
            'cooldown_seconds' => $cooldownMs === null ? null : $cooldownMs / 1000,
            'charges'          => $spell->charges,
            'cooldown_source'  => $spell->cooldown_source?->value,
            'specializations'  => $spell->specializations->isEmpty() ? null : $spell->specializations
                ->sortBy('id')
                ->map(static fn(CharacterClassSpecialization $specialization) => [
                    'id'    => $specialization->id,
                    'class' => $specialization->class->key,
                    'key'   => $specialization->key,
                ])
                ->values()
                ->all(),
        ];
    }

    private function knownOrNull(?string $value): ?string
    {
        return $value === null || $value === '' || str_ends_with($value, self::UNKNOWN_SUFFIX) || $value === 'unknown' ? null : $value;
    }

    /**
     * @return list<string>
     */
    private function getSchools(int $schoolsMask): array
    {
        $schools = [];
        foreach (SpellSchool::cases() as $school) {
            if (($schoolsMask & $school->value) !== 0) {
                $schools[] = $school->slug();
            }
        }

        return $schools;
    }
}
