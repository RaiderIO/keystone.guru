<?php

namespace App\Service\Spell\Description;

use App\Models\Spell\Spell;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;
use App\Service\Spell\Description\Logging\SpellDamageCalibrationServiceLoggingInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Closure;

/**
 * {@inheritDoc}
 *
 * Our rendered description and the game's come from the same template, so their numbers appear in the
 * same order and pair up positionally. A pair that is equal is a literal - a duration, a radius; a pair
 * that differs gives a candidate multiplier. A spell is only trusted when every differing pair agrees
 * on the same one, which is what makes a mis-paired description fail loudly rather than quietly.
 */
class SpellDamageCalibrationService implements SpellDamageCalibrationServiceInterface
{
    /** The game stores creature damage in tenths of the content's expected damage. */
    private const float COEFFICIENT_SCALE = 10.0;

    /**
     * Below this a difference is a rounding artefact rather than a scaled amount - a real multiplier is
     * in the thousands.
     */
    private const float SCALED_THRESHOLD = 100.0;

    /**
     * Wowhead rounds what it prints, so two measurements of the same multiplier land a hair apart. This
     * is a fraction rather than an absolute, since the multipliers themselves run into the tens of
     * thousands - measured absolutely, every run would rewrite every multiplier and churn the seeder.
     */
    private const float AGREEMENT_TOLERANCE = 0.001;

    public function __construct(
        private readonly WowheadServiceInterface                       $wowheadService,
        private readonly SpellDamageCalibrationServiceLoggingInterface $log,
    ) {
    }

    public function calibrate(bool $force = false, ?int $spellId = null, ?Closure $onProgress = null): array
    {
        $spells = Spell::query()
            ->whereNotNull('description_format')
            ->when($spellId !== null, fn($query) => $query->whereKey($spellId))
            ->when(!$force && $spellId === null, fn($query) => $query->whereNull('damage_multiplier'))
            ->get(['id', 'description_values', 'damage_multiplier']);

        $result  = ['measured' => 0, 'unchanged' => 0, 'disagreed' => 0, 'unreadable' => 0];
        $handled = 0;

        try {
            $this->log->calibrateStart($spells->count());

            foreach ($spells as $spell) {
                $handled++;

                ['multiplier' => $multiplier, 'disagreed' => $disagreed] = $this->measure($spell);

                if ($multiplier === null) {
                    $result[$disagreed ? 'disagreed' : 'unreadable']++;
                } elseif ($spell->damage_multiplier !== null
                    && abs($spell->damage_multiplier - $multiplier) < $spell->damage_multiplier * self::AGREEMENT_TOLERANCE) {
                    $result['unchanged']++;
                } else {
                    Spell::query()->whereKey($spell->id)->update(['damage_multiplier' => $multiplier]);
                    $result['measured']++;
                }

                if ($onProgress !== null) {
                    $onProgress($handled, $spells->count());
                }
            }

            new Spell()->flushCache();
        } finally {
            $this->log->calibrateEnd();
        }

        return $result;
    }

    /**
     * The multiplier this spell's numbers agree on, or null when they do not agree or cannot be paired -
     * `disagreed` tells those two apart, since a disagreement means we paired them up wrongly.
     *
     * @return array{multiplier: float|null, disagreed: bool}
     */
    private function measure(Spell $spell): array
    {
        $values = array_map(SpellDescriptionValue::fromArray(...), $spell->description_values ?? []);

        if ($values === []) {
            return ['multiplier' => null, 'disagreed' => false];
        }

        $tooltip = $this->wowheadService->getSpellTooltipText($spell->id);

        if ($tooltip === null) {
            return ['multiplier' => null, 'disagreed' => false];
        }

        $theirNumbers = $this->extractNumbers($tooltip);

        if (count($theirNumbers) < count($values)) {
            return ['multiplier' => null, 'disagreed' => false];
        }

        // The description is the last block of the tooltip, after the name and the range and cast line
        $paired = array_slice($theirNumbers, -count($values));
        $agreed = [];

        foreach ($values as $index => $value) {
            // Measured against the coefficient rather than against what we display, because what we
            // display already has a multiplier in it once one has been measured - comparing that would
            // report a multiplier of one and quietly undo the previous run
            if ($value->coefficient === null || $value->coefficient <= 0.0) {
                continue;
            }

            $candidate = $paired[$index] / ($value->coefficient / self::COEFFICIENT_SCALE);

            if ($candidate > self::SCALED_THRESHOLD) {
                $agreed[] = $candidate;
            }
        }

        if ($agreed === []) {
            return ['multiplier' => null, 'disagreed' => false];
        }

        // Every scaled number in one description is scaled by the same thing; if ours are not, we paired
        // them up wrongly and the measurement is worthless
        foreach ($agreed as $candidate) {
            if (abs($candidate - $agreed[0]) > $agreed[0] * self::AGREEMENT_TOLERANCE) {
                $this->log->calibrateDisagreed($spell->id, $agreed);

                return ['multiplier' => null, 'disagreed' => true];
            }
        }

        return ['multiplier' => round($agreed[0], 4), 'disagreed' => false];
    }

    /**
     * @return array<int, float>
     */
    private function extractNumbers(string $text): array
    {
        if (preg_match_all('/\d[\d,]*(?:\.\d+)?/', $text, $matches) === false) {
            return [];
        }

        return array_map(static fn(string $number): float => (float)str_replace(',', '', $number), $matches[0]);
    }
}
