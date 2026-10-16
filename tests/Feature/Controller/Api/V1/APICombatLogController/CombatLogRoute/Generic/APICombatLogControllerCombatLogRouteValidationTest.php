<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Generic;

use App\Models\DungeonKey;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('CombatLogRouteGeneric')]
class APICombatLogControllerCombatLogRouteValidationTest extends APICombatLogControllerCombatLogRouteTestBase
{
    private const string FIXTURE = 'TWW/tww_s1_the_stonevault_2';

    protected function getDungeonKey(): string
    {
        return DungeonKey::THE_STONEVAULT->value;
    }

    #[Test]
    #[DataProvider('create_givenPayloadWithoutField_returnsValidationErrorForThatField_dataProvider')]
    public function create_givenPayloadWithoutField_returnsValidationErrorForThatField(string $field): void
    {
        // Arrange
        $postBody = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
        $this->assertTrue(Arr::has($postBody, $field), sprintf('The fixture must carry %s for this test to remove it', $field));
        Arr::forget($postBody, $field);

        $response = null;

        try {
            // Act
            $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

            // Assert
            $response->assertUnprocessable();
            $this->assertSame([$field], array_keys($response->json('data')));
        } finally {
            // Only reached when the field was wrongly accepted and a route was stored after all
            if ($response?->status() === 201) {
                $this->deleteDungeonRouteByPublicKey($response->json('data.publicKey'));
            }
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function create_givenPayloadWithoutField_returnsValidationErrorForThatField_dataProvider(): array
    {
        $fields = [
            'metadata.period',
            'metadata.season',
            'metadata.regionId',
            'metadata.realmType',
            'metadata.wowInstanceId',
            'roster.averageItemLevel',
            'roster.characterIds',
            'roster.specIds',
            'roster.classIds',
            'challengeMode.timerFraction',
            'challengeMode.success',
            'challengeMode.affixes',
            'playerDeaths.0.characterId',
            'playerDeaths.0.classId',
            'playerDeaths.0.specId',
            'playerDeaths.0.itemLevel',
            'playerDeaths.0.diedAt',
            'playerDeaths.0.coord.x',
            'playerDeaths.0.coord.y',
            'playerDeaths.0.coord.uiMapId',
        ];

        return array_combine($fields, array_map(static fn(string $field): array => [$field], $fields));
    }

    #[Test]
    public function create_givenNullableFieldsNullAndEmptyArrays_returnsValidDungeonRoute(): void
    {
        // Arrange - what Raider.IO sends for a run without a result, affixes, item levels or unredacted characters
        $postBody                                   = $this->getJsonData(self::FIXTURE, self::FIXTURES_ROOT_DIR);
        $postBody['metadata']['wowInstanceId']      = null;
        $postBody['roster']['averageItemLevel']     = null;
        $postBody['roster']['characterIds']         = [];
        $postBody['roster']['specIds']              = [];
        $postBody['roster']['classIds']             = [];
        $postBody['challengeMode']['success']       = null;
        $postBody['challengeMode']['affixes']       = [];
        $postBody['challengeMode']['timerFraction'] = 0;
        $postBody['playerDeaths'][0]['characterId'] = 0;
        $postBody['playerDeaths'][0]['classId']     = 0;
        $postBody['playerDeaths'][0]['specId']      = 0;
        $postBody['playerDeaths'][0]['itemLevel']   = 0;

        // Act
        $responseArr = $this->storeCombatLogRoute($postBody);

        // Assert
        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
    }
}
