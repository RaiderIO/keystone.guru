<?php

namespace App\Logic\CombatLog\Traits;

use App\Logic\CombatLog\CombatLogLineage;
use InvalidArgumentException;

/**
 * Resolves a combat log version to the class that parses its format: the class with the greatest
 * first version at or below it, within the version's own lineage. A build that is not registered in
 * CombatLogVersion yet resolves to the newest format of its lineage.
 */
trait ResolvesCombatLogFormat
{
    /** @var array<int, class-string> */
    private static array $resolvedClassNames = [];

    /**
     * Per lineage, the first combat log version that uses each class, in ascending version order.
     *
     * @return array<value-of<CombatLogLineage>, array<int, class-string>>
     */
    abstract public static function getClassNamesByFirstCombatLogVersion(): array;

    /**
     * @return class-string
     *
     * @throws InvalidArgumentException
     */
    public static function resolveClassName(int $combatLogVersion): string
    {
        return self::$resolvedClassNames[$combatLogVersion] ??= self::findClassName($combatLogVersion);
    }

    /**
     * @return class-string
     *
     * @throws InvalidArgumentException
     */
    private static function findClassName(int $combatLogVersion): string
    {
        $lineage   = CombatLogLineage::fromCombatLogVersion($combatLogVersion);
        $className = null;
        foreach (static::getClassNamesByFirstCombatLogVersion()[$lineage->value] ?? [] as $firstCombatLogVersion => $candidateClassName) {
            if ($firstCombatLogVersion > $combatLogVersion) {
                break;
            }

            $className = $candidateClassName;
        }

        if ($className === null) {
            throw new InvalidArgumentException(
                sprintf('%s has no %s format for combat log version %d', static::class, $lineage->value, $combatLogVersion),
            );
        }

        return $className;
    }
}
