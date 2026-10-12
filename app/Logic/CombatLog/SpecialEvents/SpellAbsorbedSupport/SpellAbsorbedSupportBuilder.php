<?php

namespace App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport\Versions\V20\SpellAbsorbedSupportV20;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbedSupport\Versions\V9\SpellAbsorbedSupportV9;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use Illuminate\Support\Carbon;

class SpellAbsorbedSupportBuilder implements SpecialEventBuilderInterface
{
    use ResolvesCombatLogFormat;

    /**
     * @param  array<int, mixed>                          $parameters
     * @return SpecialEvent&SpellAbsorbedSupportInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        /** @var class-string<SpecialEvent&SpellAbsorbedSupportInterface> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC => SpellAbsorbedSupportV9::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => SpellAbsorbedSupportV20::class,
            ],
        ];
    }
}
