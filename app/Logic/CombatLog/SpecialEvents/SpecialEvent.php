<?php

namespace App\Logic\CombatLog\SpecialEvents;

use App\Logic\CombatLog\BaseEvent;
use Illuminate\Support\Str;

abstract class SpecialEvent extends BaseEvent
{
    public const SPECIAL_EVENT_COMBAT_LOG_VERSION   = 'COMBAT_LOG_VERSION';
    public const SPECIAL_EVENT_ZONE_CHANGE          = 'ZONE_CHANGE';
    public const SPECIAL_EVENT_MAP_CHANGE           = 'MAP_CHANGE';
    public const SPECIAL_EVENT_CHALLENGE_MODE_START = 'CHALLENGE_MODE_START';
    public const SPECIAL_EVENT_CHALLENGE_MODE_END   = 'CHALLENGE_MODE_END';
    public const SPECIAL_EVENT_COMBATANT_INFO       = 'COMBATANT_INFO';

    public const SPECIAL_EVENT_ALL = [
        self::SPECIAL_EVENT_COMBAT_LOG_VERSION,
        self::SPECIAL_EVENT_ZONE_CHANGE,
        self::SPECIAL_EVENT_MAP_CHANGE,
        self::SPECIAL_EVENT_CHALLENGE_MODE_START,
        self::SPECIAL_EVENT_CHALLENGE_MODE_END,
        self::SPECIAL_EVENT_COMBATANT_INFO,
    ];

    private const SPECIAL_EVENT_CLASS_MAPPING = [
        self::SPECIAL_EVENT_COMBAT_LOG_VERSION   => CombatLogVersion::class,
        self::SPECIAL_EVENT_ZONE_CHANGE          => ZoneChange::class,
        self::SPECIAL_EVENT_MAP_CHANGE           => MapChange::class,
        self::SPECIAL_EVENT_CHALLENGE_MODE_START => ChallengeModeStart::class,
        self::SPECIAL_EVENT_CHALLENGE_MODE_END   => ChallengeModeEnd::class,
        self::SPECIAL_EVENT_COMBATANT_INFO       => CombatantInfo::class,
    ];


    private function __construct(string $eventName, array $parameters)
    {
        parent::__construct($eventName);

        $this->setParameters($parameters);
    }

    /**
     * @param string $eventName
     * @param array $parameters
     * @return void
     */
    public static function createFromEventName(string $eventName, array $parameters): ?SpecialEvent
    {
        $result = null;

        foreach (self::SPECIAL_EVENT_CLASS_MAPPING as $specialEvent => $className) {
            if (Str::startsWith($eventName, $specialEvent)) {
                $result = new $className($eventName, $parameters);
                break;
            }
        }

        return $result;
    }
}
