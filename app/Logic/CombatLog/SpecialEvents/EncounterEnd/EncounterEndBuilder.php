<?php

namespace App\Logic\CombatLog\SpecialEvents\EncounterEnd;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V20\EncounterEndV20;
use App\Logic\CombatLog\SpecialEvents\EncounterEnd\Versions\V9\EncounterEndV9;
use App\Logic\CombatLog\SpecialEvents\SpecialEvent;
use App\Logic\CombatLog\SpecialEvents\SpecialEventBuilderInterface;
use App\Logic\CombatLog\Traits\ResolvesCombatLogFormat;
use Illuminate\Support\Carbon;

class EncounterEndBuilder implements SpecialEventBuilderInterface
{
    use ResolvesCombatLogFormat;

    /**
     * @param  array<int, mixed>                  $parameters
     * @return SpecialEvent&EncounterEndInterface
     */
    public static function create(
        int    $combatLogVersion,
        Carbon $timestamp,
        string $eventName,
        array  $parameters,
        string $rawEvent,
    ): SpecialEvent {
        /** @var class-string<SpecialEvent&EncounterEndInterface> $className */
        $className = self::resolveClassName($combatLogVersion);

        return new $className($combatLogVersion, $timestamp, $eventName, $parameters, $rawEvent);
    }

    public static function getClassNamesByFirstCombatLogVersion(): array
    {
        return [
            CombatLogLineage::Classic->value => [
                CombatLogVersion::CLASSIC => EncounterEndV9::class,
            ],
            CombatLogLineage::Retail->value => [
                CombatLogVersion::RETAIL_10_1_0 => EncounterEndV20::class,
            ],
        ];
    }
}
