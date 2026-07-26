<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Metrics\Metric;
use App\Models\PublishedState;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('Metric')]
final class AjaxMetricControllerTest extends AjaxPublicTestCase
{
    #[Test]
    public function storeDungeonRoute_givenUnpublishedRouteAndNonOwner_returnsForbidden(): void
    {
        // Arrange - an unpublished route the actor may not even view
        $nonOwner = User::factory()->create();
        $route    = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'expires_at'         => null,
        ]);

        try {
            $this->actingAs($nonOwner);

            // Act
            $response = $this->post(sprintf('/ajax/metric/route/%s', $route->public_key), [
                'category' => Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
                'tag'      => Metric::TAG_MDT_COPY_VIEW,
                'value'    => 1,
            ]);

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $route->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function storeDungeonRoute_givenPublishedRoute_storesTheMetric(): void
    {
        // Arrange
        $route = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
        ]);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/metric/route/%s', $route->public_key), [
                'category' => Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
                'tag'      => Metric::TAG_MDT_COPY_VIEW,
                'value'    => 1,
            ]);

            // Assert
            $response->assertNoContent();
        } finally {
            Metric::query()
                ->where('model_class', DungeonRoute::class)
                ->where('model_id', $route->id)
                ->delete();
            $route->delete();
        }
    }

    #[Test]
    public function store_givenUnviewableRouteReportedThroughGenericEndpoint_returnsForbidden(): void
    {
        // Arrange - the same route/actor combination as the storeDungeonRoute forbidden case,
        // but reported through the generic model_class/model_id endpoint instead
        $nonOwner = User::factory()->create();
        $route    = DungeonRoute::factory()->create([
            'author_id'          => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'expires_at'         => null,
        ]);

        try {
            $this->actingAs($nonOwner);

            // Act
            $response = $this->post('/ajax/metric', [
                'model_id'    => $route->id,
                'model_class' => DungeonRoute::class,
                'category'    => Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
                'tag'         => Metric::TAG_MDT_COPY_VIEW,
                'value'       => 1,
            ]);

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $route->delete();
            $nonOwner->delete();
        }
    }

    #[Test]
    public function store_givenNonDungeonRouteModelClass_storesTheMetricWithoutAuthorization(): void
    {
        // Arrange - the generic endpoint is also used for non-DungeonRoute metrics; those must
        // keep working unauthenticated/ungated
        try {
            // Act
            $response = $this->post('/ajax/metric', [
                'model_id'    => null,
                'model_class' => null,
                'category'    => Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY,
                'tag'         => Metric::TAG_MDT_COPY_VIEW,
                'value'       => 1,
            ]);

            // Assert
            $response->assertNoContent();
        } finally {
            Metric::query()
                ->whereNull('model_class')
                ->whereNull('model_id')
                ->where('category', Metric::CATEGORY_DUNGEON_ROUTE_MDT_COPY)
                ->where('tag', Metric::TAG_MDT_COPY_VIEW)
                ->delete();
        }
    }
}
