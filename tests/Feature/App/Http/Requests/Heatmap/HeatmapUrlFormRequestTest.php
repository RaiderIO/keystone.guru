<?php

namespace Tests\Feature\App\Http\Requests\Heatmap;

use App\Http\Requests\Heatmap\HeatmapUrlFormRequest;
use App\Models\GameServerRegion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('HeatmapUrlFormRequest')]
final class HeatmapUrlFormRequestTest extends PublicTestCase
{
    #[Test]
    public function rules_givenRegionKeyWhoseLegacyShortDiffers_passes(): void
    {
        // Arrange
        $id = GameServerRegion::ALL[GameServerRegion::EUROPE];
        GameServerRegion::query()->whereKey($id)->update(['short' => sprintf('legacy_%s', GameServerRegion::EUROPE)]);

        try {
            // Act
            $validator = Validator::make(['region' => GameServerRegion::EUROPE], $this->getRegionRules());

            // Assert
            $this->assertTrue($validator->passes(), $validator->errors()->toJson());
        } finally {
            GameServerRegion::query()->whereKey($id)->update(['short' => GameServerRegion::EUROPE]);
        }
    }

    #[Test]
    public function rules_givenUnknownRegion_failsOnRegion(): void
    {
        // Arrange
        $data = ['region' => 'xx'];

        // Act
        $validator = Validator::make($data, $this->getRegionRules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('region'));
    }

    /**
     * @return array<string, mixed>
     */
    private function getRegionRules(): array
    {
        return Arr::only(new HeatmapUrlFormRequest()->rules(), ['region']);
    }
}
