<?php

namespace Tests\Feature\View\Common\Forms;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('MapSettings')]
final class MapSettingsTest extends PublicTestCase
{
    #[Test]
    public function render_givenNoCookies_leavesDangerousBorderBoxUnchecked(): void
    {
        // Arrange
        unset($_COOKIE['map_enemy_dangerous_border']);

        // Act
        $html = view('common.forms.mapsettings', ['edit' => false])->render();

        // Assert
        $this->assertFalse($this->isInputChecked($html, 'map_settings_enemy_dangerous_border'));
    }

    #[Test]
    public function render_givenDangerousBorderCookieEnabled_checksDangerousBorderBox(): void
    {
        // Arrange
        $_COOKIE['map_enemy_dangerous_border'] = '1';

        try {
            // Act
            $html = view('common.forms.mapsettings', ['edit' => false])->render();

            // Assert
            $this->assertTrue($this->isInputChecked($html, 'map_settings_enemy_dangerous_border'));
        } finally {
            unset($_COOKIE['map_enemy_dangerous_border']);
        }
    }

    #[Test]
    public function render_givenNoCookies_leavesAggressivenessBorderBoxUnchecked(): void
    {
        // Arrange
        unset($_COOKIE['map_enemy_aggressiveness_border']);

        // Act
        $html = view('common.forms.mapsettings', ['edit' => false])->render();

        // Assert
        $this->assertFalse($this->isInputChecked($html, 'map_settings_enemy_aggressiveness_border'));
    }

    #[Test]
    public function render_givenEnemyForcesNumberStyleCookie_leavesPercentageNumberStyleUnchecked(): void
    {
        // Arrange
        $_COOKIE['map_number_style'] = 'enemy_forces';

        try {
            // Act
            $html = view('common.forms.mapsettings', ['edit' => false])->render();

            // Assert
            $this->assertFalse($this->isInputChecked($html, 'killzones_pulls_settings_map_number_style'));
        } finally {
            unset($_COOKIE['map_number_style']);
        }
    }

    #[Test]
    public function render_givenPercentageNumberStyleCookie_checksPercentageNumberStyle(): void
    {
        // Arrange
        $_COOKIE['map_number_style'] = 'percentage';

        try {
            // Act
            $html = view('common.forms.mapsettings', ['edit' => false])->render();

            // Assert
            $this->assertTrue($this->isInputChecked($html, 'killzones_pulls_settings_map_number_style'));
        } finally {
            unset($_COOKIE['map_number_style']);
        }
    }

    private function isInputChecked(string $html, string $id): bool
    {
        $this->assertSame(1, preg_match(sprintf('/<input[^>]*id="%s"[^>]*>/', preg_quote($id, '/')), $html, $matches), sprintf('Input #%s not found', $id));

        return (bool)preg_match('/\schecked(=|\s|>)/', $matches[0]);
    }
}
