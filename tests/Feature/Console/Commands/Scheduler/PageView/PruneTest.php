<?php

namespace Tests\Feature\Console\Commands\Scheduler\PageView;

use App\Console\Commands\Scheduler\PageView\Prune;
use App\Models\PageView;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('PageView')]
final class PruneTest extends PublicTestCase
{
    /** @var array<int> */
    private array $createdIds = [];

    #[\Override]
    protected function tearDown(): void
    {
        try {
            PageView::query()->whereIn('id', $this->createdIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenOldPageViews_deletesThemAndKeepsRecent(): void
    {
        // Arrange
        $retentionDays = config('keystoneguru.page_views.retention_days');

        $old = PageView::forceCreate([
            'user_id'     => -1,
            'model_id'    => 999999,
            'model_class' => 'TestModel',
            'session_id'  => 'test-session-old',
            'source'      => null,
            'created_at'  => Carbon::now()->subDays($retentionDays + 1),
            'updated_at'  => Carbon::now()->subDays($retentionDays + 1),
        ]);
        $this->createdIds[] = $old->id;

        $recent = PageView::forceCreate([
            'user_id'     => -1,
            'model_id'    => 999999,
            'model_class' => 'TestModel',
            'session_id'  => 'test-session-recent',
            'source'      => null,
            'created_at'  => Carbon::now()->subDays($retentionDays - 1),
            'updated_at'  => Carbon::now()->subDays($retentionDays - 1),
        ]);
        $this->createdIds[] = $recent->id;

        // Act
        $this->artisan(Prune::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('page_views', ['id' => $old->id]);
        $this->assertDatabaseHas('page_views', ['id' => $recent->id]);

        // Remove recent from cleanup since the command already removed old
        $this->createdIds = array_filter($this->createdIds, static fn(int $id) => $id === $recent->id);
    }
}
