<?php

namespace App\Logic\CombatLog\CombatEvents;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataBuilder;
use App\Logic\CombatLog\CombatEvents\GenericData\GenericDataInterface;
use App\Logic\CombatLog\CombatEvents\Prefixes\Prefix;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use Exception;

class CombatLogEvent extends BaseEvent
{
    protected GenericDataInterface $genericData;

    protected Prefix $prefix;

    protected Suffix $suffix;

    /**
     * @return $this
     * @throws Exception
     */
    public function setParameters(array $parameters): CombatLogEvent
    {
        $this->genericData = GenericDataBuilder::create($this->getCombatLogVersion());
        $this->genericData->setParameters(array_slice($parameters, 0, $this->genericData->getParameterCount()));

        $this->prefix = Prefix::createFromEventName($this->getCombatLogVersion(), $this->getEventName());
        $this->prefix->setParameters(array_slice($parameters, $this->genericData->getParameterCount(), $this->prefix->getParameterCount()));

        $this->suffix = Suffix::createFromEventName($this->getCombatLogVersion(), $this->getEventName());
        $this->suffix->setParameters(
            array_slice($parameters, $this->genericData->getParameterCount() + $this->prefix->getParameterCount())
        );

        return $this;
    }

    /**
     * @return GenericDataInterface
     */
    public function getGenericData(): GenericDataInterface
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
