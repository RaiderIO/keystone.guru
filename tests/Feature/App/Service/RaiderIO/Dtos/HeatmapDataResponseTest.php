<?php

namespace Tests\Feature\App\Service\RaiderIO\Dtos;

use App\Models\CombatLog\CombatLogEventDataType;
use App\Service\RaiderIO\Dtos\HeatmapDataResponse\HeatmapDataResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('HeatmapDataResponse')]
final class HeatmapDataResponseTest extends PublicTestCase
{
    #[Test]
    public function toArray_givenResponseWithoutHotspots_returnsTheResponseUnchanged(): void
    {
        // Arrange
        $response = $this->makeResponseArray();

        // Act
        $result = HeatmapDataResponse::fromArray($response)->toArray();

        // Assert
        $this->assertSame($this->toJsonShape($response), $result);
        $this->assertArrayNotHasKey('hotspots', $result);
    }

    #[Test]
    public function toArray_givenResponseWithHotspots_returnsThemUnchanged(): void
    {
        // Arrange
        $response             = $this->makeResponseArray();
        $response['hotspots'] = [
            [
                'floor_id' => 497,
                'hotspots' => [
                    [
                        'lat_lngs' => [['lat' => -10.5, 'lng' => 20.25], ['lat' => -11.0, 'lng' => 21.0], ['lat' => -12.0, 'lng' => 20.0]],
                        'center'   => ['lat' => -11.17, 'lng' => 20.42],
                        'deaths'   => 40,
                        'npcs'     => [
                            ['npc_id' => 3636, 'name' => 'Deviate Ravager', 'portrait_url' => 'images/enemyportraits/3636.png', 'deaths' => 30, 'percentage' => 75.0],
                            ['npc_id' => 448, 'name' => null, 'portrait_url' => null, 'deaths' => 10, 'percentage' => 25.0],
                        ],
                    ],
                ],
            ],
            ['floor_id' => 474, 'hotspots' => []],
        ];

        // Act
        $result = HeatmapDataResponse::fromArray($response)->toArray();

        // Assert
        $this->assertSame($this->toJsonShape($response), $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeResponseArray(): array
    {
        return [
            'data'        => [['floor_id' => 474, 'lat_lngs' => [['lat' => -10.5, 'lng' => 20.25, 'weight' => 3]]]],
            'data_type'   => CombatLogEventDataType::PlayerPosition,
            'run_count'   => 12,
            'weight_max'  => 3,
            'grid_size_x' => 300,
            'grid_size_y' => 200,
            'url'         => null,
        ];
    }

    /**
     * @param  array<string, mixed> $response
     * @return array<string, mixed>
     */
    private function toJsonShape(array $response): array
    {
        $response['data_type'] = $response['data_type']->value;

        return $response;
    }
}
