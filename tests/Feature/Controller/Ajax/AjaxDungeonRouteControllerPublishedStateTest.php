<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\PublishedState;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Controller\DungeonRouteTestBase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerPublishedStateTest extends DungeonRouteTestBase
{
    #[Test]
    public function publishedState_givenEverySeededPublishedStateKey_passesValidation(): void
    {
        // Arrange
        $publishedStateKeys = PublishedState::query()->orderBy('id')->pluck('key');
        $this->assertCount(count(PublishedState::ALL), $publishedStateKeys);

        foreach ($publishedStateKeys as $publishedStateKey) {
            // Act
            $response = $this->postJson(route('api.dungeonroute.publishedstate', ['dungeonRoute' => $this->dungeonRoute]), [
                'published_state' => $publishedStateKey,
            ]);

            // Assert
            $response->assertJsonMissingValidationErrors(['published_state']);
        }
    }

    #[Test]
    public function publishedState_givenUnpublishedKey_savesPublishedState(): void
    {
        // Arrange
        $this->dungeonRoute->update(['published_state_id' => PublishedState::ALL[PublishedState::TEAM]]);
        $unpublishedKey = PublishedState::query()
            ->whereKey(PublishedState::ALL[PublishedState::UNPUBLISHED])
            ->value('key');

        // Act
        $response = $this->postJson(route('api.dungeonroute.publishedstate', ['dungeonRoute' => $this->dungeonRoute]), [
            'published_state' => $unpublishedKey,
        ]);

        // Assert
        $response->assertNoContent();
        $this->assertSame(PublishedState::ALL[PublishedState::UNPUBLISHED], $this->dungeonRoute->fresh()->published_state_id);
    }

    #[Test]
    public function publishedState_givenUnknownKey_returnsValidationError(): void
    {
        // Arrange
        $this->dungeonRoute->update(['published_state_id' => PublishedState::ALL[PublishedState::TEAM]]);

        // Act
        $response = $this->postJson(route('api.dungeonroute.publishedstate', ['dungeonRoute' => $this->dungeonRoute]), [
            'published_state' => 'not_a_published_state',
        ]);

        // Assert
        $response->assertJsonValidationErrors(['published_state']);
        $this->assertSame(PublishedState::ALL[PublishedState::TEAM], $this->dungeonRoute->fresh()->published_state_id);
    }
}
