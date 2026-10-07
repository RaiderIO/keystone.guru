<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonRouteTable')]
final class TableTest extends PublicTestCase
{
    #[Test]
    public function render_givenALockedViewModeWithFilters_returnsTheFiltersWithoutTheViewModeToggle(): void
    {
        // Arrange
        $viewData = [
            'view'           => 'profile_select',
            'tableId'        => 'test_routes_table',
            'lockedViewMode' => 'list',
            'showFilters'    => true,
        ];

        // Act
        $html = view('common.dungeonroute.table', $viewData)->render();

        // Assert
        $this->assertStringContainsString('class="row g-0 test_routes_table_filter_container"', $html);
        $this->assertStringContainsString('id="dungeonroute_filter"', $html);
        $this->assertStringNotContainsString('table_list_view_toggle', $html);
    }

    #[Test]
    public function render_givenFiltersHiddenWithoutALockedViewMode_returnsTheViewModeToggleWithoutTheFilters(): void
    {
        // Arrange
        $viewData = [
            'view'        => 'profile_select',
            'tableId'     => 'test_routes_table',
            'showFilters' => false,
        ];

        // Act
        $html = view('common.dungeonroute.table', $viewData)->render();

        // Assert
        $this->assertStringContainsString('class="row g-0 test_routes_table_filter_container"', $html);
        $this->assertStringContainsString('data-viewmode="list"', $html);
        $this->assertStringNotContainsString('id="dungeonroute_filter"', $html);
    }
}
