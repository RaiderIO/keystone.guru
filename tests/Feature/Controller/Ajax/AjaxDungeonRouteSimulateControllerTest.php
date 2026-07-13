<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('SimulationCraft')]
final class AjaxDungeonRouteSimulateControllerTest extends DungeonRouteTestBase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Without Accept: application/json the ValidationException handler redirects (302)
        // instead of returning a JSON 422 response.
        $this->defaultHeaders['Accept'] = 'application/json';
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'key_level'                      => 20,
            'shrouded_bounty_type'           => SimulationCraftRaidEventsOptions::SHROUDED_BOUNTY_TYPE_NONE,
            'affix'                          => [SimulationCraftRaidEventsOptions::AFFIX_FORTIFIED],
            'thundering_clear_seconds'       => 0,
            'raid_buffs_mask'                => 0,
            'hp_percent'                     => 100,
            'ranged_pull_compensation_yards' => 0,
            'use_mounts'                     => 0,
            'simulate_bloodlust_per_pull'    => [],
        ];
    }

    private function simulateUrl(): string
    {
        return sprintf('/ajax/%s/simulate', $this->dungeonRoute->public_key);
    }

    #[Test]
    public function simulate_givenValidRequest_returnsOkWithString(): void
    {
        // Arrange - dungeon route set up by DungeonRouteTestBase

        try {
            // Act
            $response = $this->post($this->simulateUrl(), $this->validPayload());

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['string']);
        } finally {
            SimulationCraftRaidEventsOptions::where('dungeon_route_id', $this->dungeonRoute->id)->delete();
        }
    }

    /**
     * @param array<string, mixed> $override
     */
    #[Test]
    #[DataProvider('simulate_givenInvalidField_returnsUnprocessableEntity_dataProvider')]
    public function simulate_givenInvalidField_returnsUnprocessableEntity(array $override): void
    {
        // Arrange - merge override into the valid payload; a null value means the key should be absent
        $payload = $this->validPayload();
        foreach ($override as $key => $value) {
            if ($value === null) {
                unset($payload[$key]);
            } else {
                $payload[$key] = $value;
            }
        }

        // Act
        $response = $this->post($this->simulateUrl(), $payload);

        // Assert
        $response->assertUnprocessable();
    }

    /**
     * @return array<string, list<array<string, int|list<string>|string|null>>>
     */
    public static function simulate_givenInvalidField_returnsUnprocessableEntity_dataProvider(): array
    {
        // raid_buffs_mask max = 2 ** (count(SimulationCraftRaidBuffs::cases()) - 1) = 2 ** 9 = 512
        return [
            'missing key_level'                            => [['key_level' => null]],
            'key_level above max (40)'                     => [['key_level' => 41]],
            'missing shrouded_bounty_type'                 => [['shrouded_bounty_type' => null]],
            'invalid shrouded_bounty_type'                 => [['shrouded_bounty_type' => 'invalid']],
            'invalid affix item'                           => [['affix' => ['invalid_affix']]],
            'missing thundering_clear_seconds'             => [['thundering_clear_seconds' => null]],
            'thundering_clear_seconds above max (15)'      => [['thundering_clear_seconds' => 16]],
            'missing raid_buffs_mask'                      => [['raid_buffs_mask' => null]],
            'raid_buffs_mask above max (1024)'             => [['raid_buffs_mask' => 1205]],
            'missing hp_percent'                           => [['hp_percent' => null]],
            'missing ranged_pull_compensation_yards'       => [['ranged_pull_compensation_yards' => null]],
            'invalid use_mounts (not 0 or 1)'              => [['use_mounts' => 2]],
            'non-integer simulate_bloodlust_per_pull item' => [['simulate_bloodlust_per_pull' => ['not-an-int']]],
        ];
    }
}
