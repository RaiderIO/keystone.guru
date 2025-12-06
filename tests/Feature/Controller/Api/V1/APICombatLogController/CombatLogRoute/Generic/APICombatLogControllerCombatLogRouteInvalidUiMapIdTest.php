<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\Generic;

use App\Models\Dungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute\APICombatLogControllerCombatLogRouteTestBase;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('CombatLogRouteGeneric')]
class APICombatLogControllerCombatLogRouteInvalidUiMapIdTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected function getDungeonKey(): string
    {
        return Dungeon::DUNGEON_THE_STONEVAULT;
    }

    #[Test]
    #[Group('CombatLogRouteGenericNpc')]
    public function create_givenTwwS1TheStonevault4NpcInvalidUiMapIdJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Generic/tww_s1_the_stonevault_4_npc_invalid_ui_map_id', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        $this->validateSpells($responseArr, 2);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    #[Group('CombatLogRouteGenericNpc')]
    public function create_givenTwwS1TheStonevault4FirstNpcInvalidUiMapIdJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Generic/tww_s1_the_stonevault_4_npc_first_invalid_ui_map_id', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        $this->validateSpells($responseArr, 2);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    #[Group('CombatLogRouteGenericNpc')]
    public function create_givenTwwS1TheStonevault4MultipleNpcInvalidUiMapIdJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Generic/tww_s1_the_stonevault_4_npc_multiple_invalid_ui_map_id', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        $this->validateSpells($responseArr, 2);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    #[Group('CombatLogRouteGenericSpell')]
    public function create_givenTwwS1TheStonevault4SpellInvalidUiMapIdJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Generic/tww_s1_the_stonevault_4_spell_invalid_ui_map_id', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        // One cast spell falls outside the existing pulls, so is excluded
        $this->validateSpells($responseArr, 1, [2825]);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    #[Group('CombatLogRouteGenericSpell')]
    public function create_givenTwwS1TheStonevault4FirstSpellInvalidUiMapIdJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Generic/tww_s1_the_stonevault_4_spell_first_invalid_ui_map_id', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        // One cast spell falls outside the existing pulls, so is excluded
        $this->validateSpells($responseArr, 1, [2825]);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    #[Group('CombatLogRouteGenericSpell')]
    public function create_givenTwwS1TheStonevault4MultipleSpellInvalidUiMapIdJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Generic/tww_s1_the_stonevault_4_spell_multiple_invalid_ui_map_id', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        // One cast spell falls outside the existing pulls, so is excluded (there's 11 casts, 10 assignments)
        $this->validateSpells($responseArr, 3, [2825]);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }

    #[Test]
    #[Group('CombatLogRouteGenericSpell')]
    public function create_givenTwwS1TheStonevault4InvalidSpellIdJson_shouldReturnValidDungeonRoute(): void
    {
        // Arrange
        $postBody = $this->getJsonData('Generic/tww_s1_the_stonevault_4_spell_multiple_invalid_ui_map_id', self::FIXTURES_ROOT_DIR);

        // Act
        $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);

        // Assert
        $response->assertCreated();

        $responseArr = json_decode($response->content(), true);

        $this->validateResponseStaticData($responseArr);
        $this->validateDungeon($responseArr);
        $this->validatePulls($responseArr, 25, 499);
        // One cast spell falls outside the existing pulls, so is excluded (there's 11 casts, 10 assignments)
        $this->validateSpells($responseArr, 3, [2825]);
        // This was a log which did not have full affixes set - see #2483
//        $this->validateAffixes($responseArr, Affix::AFFIX_FORTIFIED, Affix::AFFIX_STORMING, Affix::AFFIX_BURSTING);
    }
}
