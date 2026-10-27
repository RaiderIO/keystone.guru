<?php

namespace Tests\Feature\View;

use App\Features\CreatorProfiles;
use App\Models\User;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The side ads sit in the gutters of the centred sitepage column, so these pages must not use the wide layout.
 */
#[Group('View')]
final class CreatorPagesLayoutTest extends PublicTestCase
{
    private const string CENTRED_CONTENT_CLASSES = 'col-md-8 offset-md-2';

    private const string WIDE_CONTENT_CLASSES = 'flex-fill ps-lg-3 pe-lg-3';

    #[Test]
    #[DataProvider('get_givenCreatorProfilesActive_rendersCentredContent_dataProvider')]
    public function get_givenCreatorProfilesActive_rendersCentredContent(string $routeName, bool $routeTakesViewer): void
    {
        // Arrange
        $viewer = User::factory()->create();
        Feature::for($viewer)->activate(CreatorProfiles::class);

        try {
            // Act
            $response = $this->actingAs($viewer)->get(route($routeName, $routeTakesViewer ? ['user' => $viewer] : []));

            // Assert
            $response->assertOk();
            $response->assertSee(self::CENTRED_CONTENT_CLASSES);
            $response->assertDontSee(self::WIDE_CONTENT_CLASSES);
        } finally {
            Feature::for($viewer)->forget(CreatorProfiles::class);
            $viewer->delete();
        }
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function get_givenCreatorProfilesActive_rendersCentredContent_dataProvider(): array
    {
        return [
            'creator directory'  => ['creators.index', false],
            'public profile'     => ['profile.view', true],
            'collection browser' => ['collection.browse', false],
        ];
    }
}
