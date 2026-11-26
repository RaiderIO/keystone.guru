<?php

namespace Tests\Feature\Controller\Api\V1\APILiveSessionCombatLogController;

use App\Jobs\LiveSession\ProcessLiveSessionCombatLogBuffer;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionCombatLogBatch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('API')]
#[Group('APILiveSessionCombatLog')]
final class APILiveSessionCombatLogControllerTest extends PublicTestCase
{
    #[Test]
    public function store_givenValidLinesAndActiveLiveSession_storesABatchAndReturnsOk(): void
    {
        // Arrange
        Queue::fake();

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                [
                    'lines' => [
                        '6/11/2026 22:23:00.0652  SPELL_CAST_START,Creature-0-3770-658-65522-229227-00002B191E,"Xal\'atath",0xa48,0x80000000,0000000000000000,nil,0x80000000,0x80000000,461870,"Xal\'atath\'s Bargain",0x20',
                        '6/11/2026 22:23:06.5252  SPELL_CAST_START,Creature-0-3770-658-65522-252567-00052B191E,"Gloombound Shadebringer",0xa48,0x80000000,0000000000000000,nil,0x80000000,0x80000000,1258431,"Shadow Bolt",0x20',
                    ],
                ],
            );

            // Assert
            $response->assertOk();
            $response->assertJson(['duplicate' => false, 'highest_sequence' => null]);
            $response->assertJsonStructure(['message']);

            $batches = $this->getBatches($liveSession);
            $this->assertCount(1, $batches);
            $this->assertNull($batches->first()->sequence);

            $lines = $this->decodeLines($batches->first());
            $this->assertCount(2, $lines);
            $this->assertSame('6/11/2026 22:23:00.0652  SPELL_CAST_START,Creature-0-3770-658-65522-229227-00002B191E,"Xal\'atath",0xa48,0x80000000,0000000000000000,nil,0x80000000,0x80000000,461870,"Xal\'atath\'s Bargain",0x20', $lines[0]);
            $this->assertSame('6/11/2026 22:23:06.5252  SPELL_CAST_START,Creature-0-3770-658-65522-252567-00052B191E,"Gloombound Shadebringer",0xa48,0x80000000,0000000000000000,nil,0x80000000,0x80000000,1258431,"Shadow Bolt",0x20', $lines[1]);

            Queue::assertPushed(ProcessLiveSessionCombatLogBuffer::class);
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
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
    public function store_givenMultipleBatches_storesEachBatchWithItsSequence(): void
    {
        // Arrange
        Queue::fake();

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

            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => $secondBatch, 'batch_sequence' => 2],
            );

            // Assert — round-trip: decompress and verify every batch kept its own lines
            $response->assertOk();
            $response->assertJson(['duplicate' => false, 'highest_sequence' => 2]);

            $batches = $this->getBatches($liveSession);
            $this->assertCount(2, $batches);
            $this->assertSame(1, $batches[0]->sequence);
            $this->assertSame($firstBatch, $this->decodeLines($batches[0]));
            $this->assertSame(2, $batches[1]->sequence);
            $this->assertSame($secondBatch, $this->decodeLines($batches[1]));
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenUnsequencedBatches_storesEveryOne(): void
    {
        // Arrange
        Queue::fake();

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act - neither batch carries a batch_sequence, so neither can be told apart from a retry
            $responses = [];
            foreach (['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000', '6/1 12:00:05.000  SPELL_CAST_SUCCESS,Player-1-000'] as $line) {
                $responses[] = $this->postJson(
                    route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                    ['lines' => [$line]],
                );
            }

            // Assert
            foreach ($responses as $response) {
                $response->assertOk();
                $response->assertJson(['duplicate' => false, 'highest_sequence' => null]);
            }

            $this->assertCount(2, $this->getBatches($liveSession));
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenDuplicateBatchSequence_skipsAndReturnsOk(): void
    {
        // Arrange
        Queue::fake();

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], 'batch_sequence' => 1],
            )->assertOk()->assertJson(['duplicate' => false]);

            // Act — re-send the same sequence number
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:09.000  SPELL_CAST_SUCCESS,Player-1-000'], 'batch_sequence' => 1],
            );

            // Assert — deduplicated, still OK, only the original batch is stored
            $response->assertOk();
            $response->assertJson([
                'message'          => __('controller.api.live_session_combat_log.duplicate_batch'),
                'duplicate'        => true,
                'highest_sequence' => 1,
            ]);

            $batches = $this->getBatches($liveSession);
            $this->assertCount(1, $batches);
            $this->assertSame(['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], $this->decodeLines($batches->first()));
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenTheSequenceOfAnAlreadyConsumedBatch_skipsIt(): void
    {
        // Arrange - the job already replayed batch 1 when the client retries it
        Queue::fake();

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $consumedBatch = LiveSessionCombatLogBatch::factory()->create([
                'live_session_id' => $liveSession->id,
                'sequence'        => 1,
                'lines'           => null,
                'consumed_at'     => Carbon::now(),
            ]);

            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], 'batch_sequence' => 1],
            );

            // Assert
            $response->assertOk();
            $response->assertJson(['duplicate' => true, 'highest_sequence' => 1]);

            $batches = $this->getBatches($liveSession);
            $this->assertCount(1, $batches);
            $this->assertSame($consumedBatch->id, $batches->first()->id);
            $this->assertNull($batches->first()->lines);
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenAnOutOfOrderSequenceNotSeenBefore_acceptsIt(): void
    {
        // Arrange
        Queue::fake();

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:05.000  SPELL_CAST_SUCCESS,Player-1-000'], 'batch_sequence' => 2],
            )->assertOk();

            // Act - batch 1 overtaken by batch 2 on the way in
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], 'batch_sequence' => 1],
            );

            // Assert
            $response->assertOk();
            $response->assertJson([
                'message'          => __('controller.api.live_session_combat_log.stored'),
                'duplicate'        => false,
                'highest_sequence' => 2,
            ]);

            $this->assertEqualsCanonicalizing([1, 2], $this->getBatches($liveSession)->pluck('sequence')->all());
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenDuplicateBatchSequence_dispatchesTheJobAgain(): void
    {
        // Arrange
        Queue::fake();

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

            // Act - a client retrying the batch because the first request failed after storing it
            $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], 'batch_sequence' => 1],
            )->assertOk();

            // Assert
            Queue::assertPushedTimes(ProcessLiveSessionCombatLogBuffer::class, 2);
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenNonAdminSessionOwner_storesABatchAndReturnsOk(): void
    {
        // Arrange
        Queue::fake();

        /** @var User $owner */
        $owner = User::factory()->create();
        $this->actingAs($owner);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['user_id' => $owner->id]);

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000']],
            );

            // Assert
            $response->assertOk();
            $this->assertTrue(LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->exists());
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function store_givenNonAdminWhoDoesNotOwnTheSession_returnsForbidden(): void
    {
        // Arrange
        Queue::fake();

        /** @var User $owner */
        $owner = User::factory()->create();
        /** @var User $viewer */
        $viewer = User::factory()->create();
        $this->actingAs($viewer);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create(['user_id' => $owner->id]);

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => ['6/1 12:00:00.000  SPELL_CAST_START,Player-1-000'], 'batch_sequence' => 999],
            );

            // Assert
            $response->assertStatus(StatusCode::FORBIDDEN);
            $this->assertFalse(LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->exists());
            Queue::assertNothingPushed();
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
            $viewer->delete();
            $owner->delete();
        }
    }

    #[Test]
    public function store_givenMoreLinesThanTheCap_returnsUnprocessableContent(): void
    {
        // Arrange
        Queue::fake();
        config(['keystoneguru.live_sessions.combat_log.max_lines_per_batch' => 2]);

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => array_fill(0, 3, '6/1 12:00:00.000  SPELL_CAST_START,Player-1-000')],
            );

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonPath('data.lines.0', __('validation.custom.live_session_combat_log_lines.max', ['max' => 2]));
            $this->assertFalse(LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->exists());
            Queue::assertNothingPushed();
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenExactlyTheCappedNumberOfLines_returnsOk(): void
    {
        // Arrange
        Queue::fake();
        config(['keystoneguru.live_sessions.combat_log.max_lines_per_batch' => 2]);

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => array_fill(0, 2, '6/1 12:00:00.000  SPELL_CAST_START,Player-1-000')],
            );

            // Assert
            $response->assertOk();
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    #[Test]
    public function store_givenALineLongerThanTheCap_returnsUnprocessableContent(): void
    {
        // Arrange
        Queue::fake();
        config(['keystoneguru.live_sessions.combat_log.max_line_length' => 50]);

        /** @var User $user */
        $user = User::findOrFail(1);
        $this->actingAs($user);

        /** @var LiveSession $liveSession */
        $liveSession = LiveSession::factory()->create();

        try {
            // Act
            $response = $this->postJson(
                route('api.v1.combatlog.livesession.events.store', ['liveSession' => $liveSession->public_key]),
                ['lines' => [str_repeat('a', 50), str_repeat('a', 51)]],
            );

            // Assert - only the line over the cap is reported
            $response->assertUnprocessable();
            $response->assertJsonPath('data', ['lines.1' => [__('validation.custom.live_session_combat_log_line.max', ['max' => 50])]]);
            $this->assertFalse(LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->exists());
        } finally {
            LiveSessionCombatLogBatch::query()->where('live_session_id', $liveSession->id)->delete();
            $liveSession->delete();
            $liveSession->dungeonRoute?->delete();
        }
    }

    /**
     * @return Collection<int, LiveSessionCombatLogBatch>
     */
    private function getBatches(LiveSession $liveSession): Collection
    {
        return LiveSessionCombatLogBatch::query()
            ->where('live_session_id', $liveSession->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function decodeLines(LiveSessionCombatLogBatch $batch): array
    {
        $decoded = gzdecode($batch->lines);
        $this->assertNotFalse($decoded);

        return explode("\n", $decoded);
    }
}
