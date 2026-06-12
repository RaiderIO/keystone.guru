<?php

namespace Tests\Feature\Controller\Api\V1\APILiveSessionCombatLogController;

use App\Models\LiveSession;
use App\Models\LiveSessionCombatLogBuffer;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

/**
 * @group Controller
 * @group API
 * @group APILiveSessionCombatLog
 */
#[Group('Controller')]
#[Group('API')]
#[Group('APILiveSessionCombatLog')]
final class APILiveSessionCombatLogControllerTest extends PublicTestCase
{
    #[Test]
    public function store_givenValidLinesAndActiveLiveSession_storesBufferAndReturnsOk(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000', '6/1 12:00:01.000  SPELL_DAMAGE,...']],
            );

            // Assert
            $response->assertOk();
            $response->assertJsonStructure(['message']);

            $buffer = LiveSessionCombatLogBuffer::query()
                ->where('live_session_id', $liveSession->id)
                ->first();

            $this->assertNotNull($buffer);
            $this->assertNotNull($buffer->buffer);

            $decoded = gzdecode($buffer->buffer);
            $this->assertNotFalse($decoded);
            $lines = explode("\n", $decoded);
            $this->assertCount(2, $lines);
            $this->assertSame('6/1 12:00:00.000  SPELL_CAST_START,Player-1-000', $lines[0]);
            $this->assertSame('6/1 12:00:01.000  SPELL_DAMAGE,...', $lines[1]);
        } finally {
            LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenExpiredLiveSession_returnsForbidden(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->expired()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000']],
            );

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenMissingLiveSession_returnsNotFound(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        // Act
        $response = $this->postJson(
            route('api.v1.combatlog.livesession.events.store', ['liveSession' => 'nonexistent']),
            ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000']],
        );

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function store_givenUnauthenticatedUser_returnsForbidden(): void
    {
        // Arrange
        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000']],
            );

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenMissingLines_returnsUnprocessableContent(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                [],
            );

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonStructure(['data' => ['lines']]);
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenEmptyLinesArray_returnsUnprocessableContent(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => []],
            );

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonStructure(['data' => ['lines']]);
        } finally {
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenMultipleBatches_accumulatesBufferCorrectly(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $firstBatch  = ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000', '6/1 12:00:01.000  UNIT_DIED,...'];
            $secondBatch = ['6/1 12:00:05.000  SPELL_CAST_SUCCESS,Player-1-000'];

            // Act
            $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => $firstBatch, 'batch_sequence' => 1],
            )->assertOk();

            $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => $secondBatch, 'batch_sequence' => 2],
            )->assertOk();

            // Assert — round-trip: decompress and verify all lines are present
            $buffer = LiveSessionCombatLogBuffer::query()
                ->where('live_session_id', $liveSession->id)
                ->first();

            $this->assertNotNull($buffer);
            $this->assertSame(2, $buffer->last_sequence);

            $decoded = gzdecode($buffer->buffer);
            $this->assertNotFalse($decoded);

            $lines = explode("\n", $decoded);
            $this->assertCount(3, $lines);
            $this->assertSame($firstBatch[0], $lines[0]);
            $this->assertSame($firstBatch[1], $lines[1]);
            $this->assertSame($secondBatch[0], $lines[2]);
        } finally {
            LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenDuplicateBatchSequence_skipsAndReturnsOk(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], 'batch_sequence' => 1],
            )->assertOk();

            // Act — re-send the same sequence number
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], 'batch_sequence' => 1],
            );

            // Assert — deduplicated, still OK, buffer has only the original line
            $response->assertOk();

            $buffer = LiveSessionCombatLogBuffer::query()
                ->where('live_session_id', $liveSession->id)
                ->first();

            $decoded = gzdecode($buffer->buffer);
            $lines   = explode("\n", $decoded);
            $this->assertCount(1, $lines);
        } finally {
            LiveSessionCombatLogBuffer::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }
}
