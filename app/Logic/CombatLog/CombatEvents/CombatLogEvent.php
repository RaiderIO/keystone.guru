<?php

namespace App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;

class CombatLogEvent extends BaseEvent
{
    protected GenericData $genericData;

    protected Prefix $prefix;

    protected Suffix $suffix;

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters): CombatLogEvent
    {
        $this->genericData = (new GenericData());
        $this->genericData->setParameters(array_slice($parameters, 0, $this->genericData->getParameterCount()));

        $this->prefix = Prefix::createFromEventName($this->getEventName());
        $this->prefix->setParameters(array_slice($parameters, $this->genericData->getParameterCount(), $this->prefix->getParameterCount()));

        $this->suffix = Suffix::createFromEventName($this->getEventName());
        $this->suffix->setParameters(
            array_slice($parameters, $this->genericData->getParameterCount() + $this->prefix->getParameterCount())
        );

        return $this;
    }

    /**
     * @return GenericData
     */
    public function getGenericData(): GenericData
    {
        return $this->genericData;
    }

    /**
     * @return Prefix
     */
    public function getPrefix(): Prefix
    {
        return $this->prefix;
    }

    /**
     * @return Suffix
     */
    public function getSuffix(): Suffix
    {
        return $this->suffix;
    }
//
//
//    /**
//     * @param string $eventName
//     * @return bool
//     */
//    public static function canParseEventName(string $eventName): bool
//    {
//        $result = false;
//
//        foreach (Prefix::PREFIX_ALL as $prefix) {
//            foreach (Suffix::SUFFIX_ALL as $suffix) {
//                if ($eventName === sprintf('%s_%s', $prefix, $suffix)) {
//                    $result = true;
//                    break 2;
//                }
//            }
//        }
//
//        return $result;
//    }
}
