<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\BFA;
use App\Models\DungeonKey;
use App\Models\Npc\NpcId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('TempleOfSethraliss')]
class APICombatLogControllerCombatLogRouteTempleOfSethralissTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return DungeonKey::TEMPLE_OF_SETHRALISS->value;
    }

    #[Test]
    public function create_givenTempleOfSethralissSeason2Json_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('BFA/midnight_s2_temple_of_sethraliss', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        try {
            $this->validateResponseStaticData($responseArr);
            $this->validateDungeon($responseArr);
            // This run completed the key (challengeMode.success), so the party necessarily reached 100% of the 687
            // enemy forces the mapping requires. Anything under that means enemies are still going missing.
            $this->validatePulls($postBody, $responseArr, 21, 688);
            $this->validateAffixes($responseArr);
            $this->validateBossesResolved($postBody, $responseArr);

            // No Static Anomaly death ever reaches us - they despawn once dealt with, and Galvazzt only spawns after
            // all of them are gone. TempleOfSethralissDespawningEnemiesRule awards them off his death, so all six
            // land in his pull. The count is asserted because crediting only one of them still satisfies presence.
            $this->validateNpcIdCount($responseArr, NpcId::STATIC_ANOMALY->value, 6);
            $this->validateNpcIdsInSamePull($responseArr, [
                NpcId::GALVAZZT_RESTORED->value,
                NpcId::STATIC_ANOMALY->value,
            ]);

            // The Avatar is won by healing it to full, so no death for it exists and validateBossesResolved() cannot
            // see it. The run completed, which is the only thing that implies it, and it is awarded into the 21st
            // pull of its own - it is on another floor than the last death, so it does not belong in that pull.
            $this->validateNpcIdCount($responseArr, NpcId::AVATAR_OF_SETHRALISS->value, 1);
            $this->assertSame(
                [NpcId::AVATAR_OF_SETHRALISS->value],
                array_column($responseArr['data']['pulls'][20]['enemies'], 'npcId'),
                'The Avatar of Sethraliss must be the whole of the final pull',
            );
        } finally {
            $this->deleteDungeonRoute($responseArr);
        }
    }
}
