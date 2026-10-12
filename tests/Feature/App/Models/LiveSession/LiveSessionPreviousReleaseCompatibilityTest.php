<?php

namespace Tests\Feature\App\Models\LiveSession;

use App\Events\LiveSession\StopEvent;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionOverpulledEnemy;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The previous release reads `overpulled_enemies` and queues payloads naming App\Models\LiveSession; both must keep
 * working until the contract release drops them.
 */
#[Group('LiveSession')]
final class LiveSessionPreviousReleaseCompatibilityTest extends PublicTestCase
{
    private const int NPC_ID = 987654;

    private ?LiveSession $liveSession = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->liveSession = LiveSession::factory()->create();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            if ($this->liveSession !== null) {
                LiveSessionOverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->delete();
                OverpulledEnemy::query()->where('live_session_id', $this->liveSession->id)->delete();
                LiveSession::query()->whereKey($this->liveSession->id)->delete();
                DungeonRoute::query()->whereKey($this->liveSession->dungeon_route_id)->first()?->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function save_givenNewOverpulledEnemy_writesLegacyMirrorRow(): void
    {
        // Arrange
        $overpulledEnemy = new LiveSessionOverpulledEnemy([
            'live_session_id' => $this->liveSession->id,
            'kill_zone_id'    => 111,
            'npc_id'          => self::NPC_ID,
            'mdt_id'          => 1,
        ]);

        // Act
        $overpulledEnemy->save();

        // Assert
        $this->assertDatabaseHas('overpulled_enemies', [
            'live_session_id' => $this->liveSession->id,
            'kill_zone_id'    => 111,
            'npc_id'          => self::NPC_ID,
            'mdt_id'          => 1,
        ]);
    }

    #[Test]
    public function save_givenChangedKillZone_updatesLegacyMirrorRow(): void
    {
        // Arrange
        $overpulledEnemy = LiveSessionOverpulledEnemy::query()->create([
            'live_session_id' => $this->liveSession->id,
            'kill_zone_id'    => 111,
            'npc_id'          => self::NPC_ID,
            'mdt_id'          => 1,
        ]);

        // Act
        $overpulledEnemy->update(['kill_zone_id' => 222]);

        // Assert
        $legacyKillZoneIds = OverpulledEnemy::query()
            ->where('live_session_id', $this->liveSession->id)
            ->pluck('kill_zone_id')
            ->all();
        $this->assertSame([222], $legacyKillZoneIds);
    }

    #[Test]
    public function delete_givenOverpulledEnemy_deletesOnlyItsLegacyMirrorRow(): void
    {
        // Arrange
        $deleted = LiveSessionOverpulledEnemy::query()->create([
            'live_session_id' => $this->liveSession->id,
            'kill_zone_id'    => 111,
            'npc_id'          => self::NPC_ID,
            'mdt_id'          => 1,
        ]);
        LiveSessionOverpulledEnemy::query()->create([
            'live_session_id' => $this->liveSession->id,
            'kill_zone_id'    => 111,
            'npc_id'          => self::NPC_ID,
            'mdt_id'          => 2,
        ]);

        // Act
        $deleted->delete();

        // Assert
        $legacyMdtIds = OverpulledEnemy::query()
            ->where('live_session_id', $this->liveSession->id)
            ->pluck('mdt_id')
            ->all();
        $this->assertSame([2], $legacyMdtIds);
    }

    #[Test]
    public function delete_givenLiveSessionWithLegacyRows_deletesThem(): void
    {
        // Arrange
        OverpulledEnemy::query()->insert([
            'live_session_id' => $this->liveSession->id,
            'kill_zone_id'    => 111,
            'npc_id'          => self::NPC_ID,
            'mdt_id'          => 1,
        ]);

        // Act
        $this->liveSession->delete();

        // Assert
        $this->assertDatabaseMissing('overpulled_enemies', ['live_session_id' => $this->liveSession->id]);
    }

    #[Test]
    public function unserialize_givenEventQueuedWithPreviousLiveSessionClass_restoresTheLiveSession(): void
    {
        // Arrange
        $this->assertTrue(class_exists('App\\Models\\LiveSession'), 'The previous release\'s LiveSession class must still exist.');
        $previousClass   = 'App\\Models\\LiveSession';
        $previousSession = $previousClass::query()->findOrFail($this->liveSession->id);
        $payload         = serialize(new StopEvent($previousSession, User::query()->findOrFail(1)));

        // Act
        /** @var StopEvent $event */
        $event = unserialize($payload);

        // Assert
        $channelNames = array_map(static fn($channel) => $channel->name, $event->broadcastOn());
        $this->assertContains(
            sprintf('presence-%s-live-session.%s', config('app.type'), $this->liveSession->getRouteKey()),
            $channelNames,
        );
    }
}
