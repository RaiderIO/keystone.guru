<?php

namespace Tests\Feature\View;

use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\View\ViewService;
use App\Service\View\ViewServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;

#[Group('ViewComposers')]
final class ViewComposerSmokeTest extends PublicTestCase
{
    #[Test]
    public function home_givenGuest_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function affixes_givenGuest_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('misc.affixes'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function search_givenGuest_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->get(route('dungeonroutes.search'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function discoverGameVersion_givenGuest_returnsOk(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act - the bare game version page redirects to a specific dungeon's discover page
        $response = $this->followingRedirects()->get(route('dungeonroutes.gameVersion', ['gameVersion' => 'retail']));

        // Assert
        $response->assertOk();
    }

    /**
     * Regression guard for the core goal of the refactor: a page that does not display the spell
     * selector (the heaviest dataset) must never resolve the selectable-spells getter. Previously
     * every request eagerly loaded all spells as part of one global blob.
     *
     * @throws Exception
     */
    #[Test]
    public function home_givenGuest_neverResolvesSelectableSpells(): void
    {
        // Arrange
        $this->actingAsGuest();
        $viewServiceSpy = $this->getMockBuilder(ViewService::class)
            ->setConstructorArgs([
                app(CacheServiceInterface::class),
                app(ExpansionServiceInterface::class),
                app(SeasonAffixGroupServiceInterface::class),
                app(AffixGroupEaseTierServiceInterface::class),
            ])
            ->onlyMethods(['getSelectableSpellsByCategory'])
            ->getMock();
        $viewServiceSpy->expects($this->never())
            ->method('getSelectableSpellsByCategory');
        $this->app->instance(ViewServiceInterface::class, $viewServiceSpy);

        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
    }
}
