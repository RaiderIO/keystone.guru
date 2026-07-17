<?php

namespace Tests\Feature\View\Common\Dungeon;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonListCard')]
final class DungeonListCardTest extends PublicTestCase
{
    /** @var array<string, mixed> */
    private const array BASE_PARAMS = [
        'id'         => 1,
        'link'       => '/routes/retail/skyreach',
        'title'      => 'SKY',
        'isSelected' => false,
        'imageUrl'   => '/images/skyreach.png',
        'imageAlt'   => 'Skyreach',
        'width'      => null,
    ];

    #[Test]
    public function render_givenThisWeekTier_showsQualityColouredTierBadge(): void
    {
        // Act
        $html = view('common.dungeon.list.card', [...self::BASE_PARAMS, 'thisWeekTier' => 'S'])->render();

        // Assert - the tier overlay renders with the WoW-quality colour class and the letter
        $this->assertStringContainsString('dungeon_card_tiers', $html);
        $this->assertStringContainsString('class="tier s"', $html);
        $this->assertStringContainsString('>S<', $html);
    }

    #[Test]
    public function render_givenNoTier_omitsTierBadge(): void
    {
        // Act
        $html = view('common.dungeon.list.card', [...self::BASE_PARAMS, 'thisWeekTier' => null])->render();

        // Assert
        $this->assertStringNotContainsString('dungeon_card_tiers', $html);
    }
}
