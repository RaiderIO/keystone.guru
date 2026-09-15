<?php

namespace Tests\Feature\App\Service\CombatLog\Builders\Rules;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Enemy;
use App\Service\CombatLog\Builders\Rules\TheBlindingValeBridgeRule;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCases\PublicTestCase;

/**
 * The rule can only exclude an enemy for as long as its unique key still resolves to one, and an import that drops or
 * renames an enemy turns the exclusion into a silent no-op. This fails here instead.
 */
#[Group('CombatLog')]
#[Group('DungeonRouteBuilderRules')]
#[Group('TheBlindingValeBridgeRule')]
class TheBlindingValeBridgeRuleMappingTest extends PublicTestCase
{
    #[Test]
    public function theBlindingValeBridgeRuleEnemies_givenTheLatestMappingVersion_stillResolveByUniqueKey(): void
    {
        // Arrange
        $dungeon        = Dungeon::where('key', DungeonKey::THE_BLINDING_VALE->value)->firstOrFail();
        $mappingVersion = $dungeon->mappingVersions()->orderByDesc('version')->firstOrFail();
        $mappedKeys     = Enemy::where('mapping_version_id', $mappingVersion->id)
            ->get()
            ->map(static fn(Enemy $enemy) => $enemy->getUniqueKey())
            ->all();

        $reflection = new ReflectionClass(TheBlindingValeBridgeRule::class);

        // Act
        $ruleKeys = array_merge(
            $reflection->getConstant('BRIDGE_ENEMY_UNIQUE_KEYS'),
            $reflection->getConstant('UNDER_BRIDGE_ENEMY_UNIQUE_KEYS'),
        );

        // Assert
        $this->assertNotEmpty($ruleKeys);

        foreach ($ruleKeys as $uniqueKey) {
            $this->assertContains(
                $uniqueKey,
                $mappedKeys,
                sprintf('Enemy %s is no longer mapped in The Blinding Vale', $uniqueKey),
            );
        }
    }
}
