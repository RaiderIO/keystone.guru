<?php

namespace App\Logic\CombatLog\SpecialEvents\SpellAbsorbed;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V20\SpellAbsorbedV20;
use App\Logic\CombatLog\SpecialEvents\SpellAbsorbed\Versions\V9\SpellAbsorbedV9;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use Illuminate\Support\Carbon;

class SpellAbsorbedBuilder implements SpecialEventBuilderInterface
{
    use ResolvesCombatLogFormat;

    /**
     * @param  array<int, mixed>                   $parameters
     * @return SpecialEvent&SpellAbsorbedInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        /** @var class-string<SpecialEvent&SpellAbsorbedInterface> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC => SpellAbsorbedV9::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => SpellAbsorbedV20::class,
            ],
        ];
    }
}
