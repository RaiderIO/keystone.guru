<?php

namespace Tests\Feature\App\Models;

use App\Models\PageView;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('PageView')]
final class PageViewTest extends PublicTestCase
{
    private const string MODEL_CLASS = 'TestModel';
    private const int    MODEL_ID    = 999999;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            PageView::query()
                ->where('model_class', self::MODEL_CLASS)
                ->where('model_id', self::MODEL_ID)
                ->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function trackPageView_givenGuestReloadWithinThreshold_returnsFalse(): void
    {
        // Arrange
        $this->actingAsGuest();
        $firstViewTracked = PageView::trackPageView(self::MODEL_ID, self::MODEL_CLASS);

        // Act
        $result = PageView::trackPageView(self::MODEL_ID, self::MODEL_CLASS);

        // Assert
        $this->assertTrue($firstViewTracked);
        $this->assertFalse($result);
        $this->assertSame(1, $this->countPageViews());
    }

    #[Test]
    public function trackPageView_givenUserViewWithinThreshold_returnsFalse(): void
    {
        // Arrange
        $this->actingAs(User::findOrFail(1));
        $this->createPageView(1, Carbon::now()->subMinutes($this->getThresholdMinutes() - 1));

        // Act
        $result = PageView::trackPageView(self::MODEL_ID, self::MODEL_CLASS);

        // Assert
        $this->assertFalse($result);
        $this->assertSame(1, $this->countPageViews());
    }

    #[Test]
    public function trackPageView_givenUserViewOlderThanThreshold_returnsTrue(): void
    {
        // Arrange
        $this->actingAs(User::findOrFail(1));
        $this->createPageView(1, Carbon::now()->subMinutes($this->getThresholdMinutes() + 1));

        // Act
        $result = PageView::trackPageView(self::MODEL_ID, self::MODEL_CLASS);

        // Assert
        $this->assertTrue($result);
        $this->assertSame(2, $this->countPageViews());
    }

    #[Test]
    public function trackPageView_givenOldAndRecentViewInSession_judgesTheRecentOne(): void
    {
        // Arrange
        $this->actingAs(User::findOrFail(1));
        $this->createPageView(1, Carbon::now()->subMinutes($this->getThresholdMinutes() * 2));
        $this->createPageView(1, Carbon::now()->subMinutes(1));

        // Act
        $result = PageView::trackPageView(self::MODEL_ID, self::MODEL_CLASS);

        // Assert
        $this->assertFalse($result);
        $this->assertSame(2, $this->countPageViews());
    }

    private function createPageView(int $userId, Carbon $createdAt): void
    {
        PageView::forceCreate([
            'user_id'     => $userId,
            'model_id'    => self::MODEL_ID,
            'model_class' => self::MODEL_CLASS,
            'session_id'  => Session::getId(),
            'source'      => null,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ]);
    }

    private function countPageViews(): int
    {
        return PageView::query()
            ->where('model_class', self::MODEL_CLASS)
            ->where('model_id', self::MODEL_ID)
            ->count();
    }

    private function getThresholdMinutes(): int
    {
        return config('keystoneguru.view_time_threshold_mins');
    }
}
