<?php

namespace Tests\Feature\View;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Every layout renders exactly one main landmark around the page content, with the header, footer and map
 * controls outside it, and a BCP 47 language tag on the html element.
 */
#[Group('View')]
#[Group('Layout')]
final class LayoutLandmarkTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const string BCP47_LANGUAGE_TAG_PATTERN = '/<html lang="([a-z]{2,3}(-[A-Z]{2})?)"/';

    #[Test]
    #[DataProvider('locales')]
    public function sitepage_givenADefaultPage_rendersOneMainBetweenHeaderAndFooter(string $locale, string $expectedLang): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withSession(['locale' => $locale])->get(route('misc.credits'));

        // Assert
        $response->assertOk();
        $html = (string)$response->getContent();

        $this->assertLangAttribute($expectedLang, $html);
        $this->assertSingleMainBetweenHeaderAndFooter($html);
        $this->assertStringContainsString('class="container-fluid mb-4', $this->mainContent($html));
    }

    #[Test]
    #[DataProvider('locales')]
    public function sitepage_givenAPageWithMenuItems_rendersOneMainBetweenHeaderAndFooter(string $locale, string $expectedLang): void
    {
        // Arrange
        $user = User::factory()->create(['locale' => $locale]);

        try {
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));

            // Act
            $response = $this->actingAs($user)->get(route('profile.edit'));

            // Assert
            $response->assertOk();
            $html = (string)$response->getContent();

            $this->assertLangAttribute($expectedLang, $html);
            $this->assertSingleMainBetweenHeaderAndFooter($html);
            $this->assertStringContainsString('nav flex-column nav-pills', $this->mainContent($html));
        } finally {
            $user->delete();
        }
    }

    #[Test]
    #[DataProvider('customRootClasses')]
    public function sitepage_givenCustomContent_rendersOneMainAroundTheContent(string $rootClass): void
    {
        // Arrange
        $originalLocale = app()->getLocale();
        app()->setLocale('de_DE_ai');

        try {
            // Act
            $html = (string)$this->blade(sprintf(
                "@extends('layouts.sitepage', ['custom' => true, 'rootClass' => '%s'])\n@section('content')<p id=\"custom_page_content\"></p>@endsection",
                $rootClass,
            ));

            // Assert
            $this->assertLangAttribute('de-DE', $html);
            $this->assertSingleMainBetweenHeaderAndFooter($html);
            $this->assertStringContainsString('id="custom_page_content"', $this->mainContent($html));
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function customRootClasses(): array
    {
        return [
            'without root class' => [''],
            'with root class'    => ['custom_root'],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function map_givenARouteViewPage_rendersTheMapAsTheOnlyMain(string $locale, string $expectedLang): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createPublishedRoute($owner);

        try {
            // Act
            $response = $this->withSession(['locale' => $locale])->followingRedirects()->get(route('dungeonroute.view', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
            $html = (string)$response->getContent();

            $this->assertLangAttribute($expectedLang, $html);
            $this->assertSame(1, substr_count($html, '<main'));
            $this->assertStringContainsString('<main id="map"', $html);
            $this->assertStringContainsString('href="#map"', $html, 'The skip link must target the main landmark');
            $this->assertLessThan(strpos($html, '<main'), strpos($html, 'id="map_header"'));
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    #[DataProvider('locales')]
    public function map_givenARouteEmbedPage_rendersTheMapAsTheOnlyMain(string $locale, string $expectedLang): void
    {
        // Arrange
        $owner = User::factory()->create();
        $route = $this->createPublishedRoute($owner);

        try {
            // Act
            $response = $this->withSession(['locale' => $locale])->get(route('dungeonroute.embed', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));

            // Assert
            $response->assertOk();
            $html = (string)$response->getContent();

            $this->assertLangAttribute($expectedLang, $html);
            $this->assertSame(1, substr_count($html, '<main'));
            $this->assertStringContainsString('<main id="map"', $html);
        } finally {
            $route->delete();
            $owner->delete();
        }
    }

    #[Test]
    #[DataProvider('locales')]
    public function map_givenAnUnsupportedHeatmapEmbed_rendersTheMessageAsTheOnlyMain(string $locale, string $expectedLang): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(
            dungeonActive:       true,
            requireDefaultFloor: true,
            constraint:          static fn(Builder $query) => $query->where('heatmap_enabled', 1),
        );

        try {
            Dungeon::query()->whereKey($dungeon->id)->update(['heatmap_enabled' => 0]);

            // Act
            $response = $this->withSession(['locale' => $locale])->get(route('dungeon.heatmap.gameversion.embed', [
                'gameVersion' => $mappingVersion->gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert
            $response->assertOk();
            $response->assertViewIs('dungeon.heatmap.gameversion.embedunsupported');
            $html = (string)$response->getContent();

            $this->assertLangAttribute($expectedLang, $html);
            $this->assertSame(1, substr_count($html, '<main'));
            $this->assertLessThan(strpos($html, '<main'), strpos($html, 'header_embed_compact'));
        } finally {
            Dungeon::query()->whereKey($dungeon->id)->update(['heatmap_enabled' => 1]);
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'regular locale'       => ['en_US', 'en-US'],
            'AI-translated locale' => ['de_DE_ai', 'de-DE'],
        ];
    }

    private function assertLangAttribute(string $expectedLang, string $html): void
    {
        $this->assertSame(1, preg_match(self::BCP47_LANGUAGE_TAG_PATTERN, $html, $matches), 'The html lang attribute must be a BCP 47 tag');
        $this->assertSame($expectedLang, $matches[1]);
    }

    private function assertSingleMainBetweenHeaderAndFooter(string $html): void
    {
        $this->assertSame(1, substr_count($html, '<main'));
        $this->assertSame(1, substr_count($html, '</main>'));

        $mainStart = strpos($html, '<main');
        $mainEnd   = strpos($html, '</main>');
        $this->assertLessThan($mainStart, strpos($html, 'id="site_header"'), 'The site header must be outside the main landmark');
        $this->assertGreaterThan($mainEnd, strpos($html, 'class="site-footer"'), 'The footer must be outside the main landmark');
        $this->assertStringContainsString('id="main_content"', $this->mainContent($html), 'The skip link target must be inside the main landmark');
    }

    private function mainContent(string $html): string
    {
        $mainStart = (int)strpos($html, '<main');

        return substr($html, $mainStart, (int)strpos($html, '</main>') - $mainStart);
    }

    /**
     * A published, non-sandbox route; the factory's sandbox default would make it unembeddable.
     */
    private function createPublishedRoute(User $owner): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => $owner->id,
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);
    }
}
