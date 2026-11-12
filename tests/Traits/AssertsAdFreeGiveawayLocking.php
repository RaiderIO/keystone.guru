<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * A true concurrency test needs two connections racing each other, so the giveaway tests pin the order instead:
 * a transaction begins, the giver's row is locked, and only then is the limit counted and the giveaway inserted.
 */
trait AssertsAdFreeGiveawayLocking
{
    /**
     * @param callable(): TestResponse<Response> $request
     */
    protected function assertGiveawayCreatedWhileGiverLocked(User $giver, callable $request): void
    {
        /** @var list<array{sql: string, bindings: array<int, mixed>}> $activity */
        $activity = [];
        Event::listen(TransactionBeginning::class, static function () use (&$activity): void {
            $activity[] = ['sql' => 'BEGIN', 'bindings' => []];
        });
        DB::listen(static function (QueryExecuted $query) use (&$activity): void {
            $activity[] = ['sql' => $query->sql, 'bindings' => $query->bindings];
        });

        $request()->assertCreated();

        $lockIndex = $this->findActivityIndex($activity, static fn(array $entry): bool => str_contains($entry['sql'], 'from `users`')
            && str_ends_with($entry['sql'], 'for update')
            && (in_array($giver->id, $entry['bindings']) || preg_match(sprintf('/\b%d\b/', $giver->id), $entry['sql']) === 1));
        $beginIndex  = $this->findActivityIndex($activity, static fn(array $entry): bool => $entry['sql'] === 'BEGIN');
        $countIndex  = $this->findActivityIndex($activity, static fn(array $entry): bool => preg_match('/^select count\(\*\) as `?aggregate`? from `patreon_ad_free_giveaways`/', $entry['sql']) === 1);
        $insertIndex = $this->findActivityIndex($activity, static fn(array $entry): bool => str_starts_with($entry['sql'], 'insert into `patreon_ad_free_giveaways`'));

        $this->assertNotNull($lockIndex, 'The giver\'s users row must be locked with FOR UPDATE');
        $this->assertNotNull($beginIndex, 'The giveaway must be created inside a transaction');
        $this->assertNotNull($countIndex, 'The giver\'s giveaways must be counted');
        $this->assertNotNull($insertIndex, 'The giveaway must be inserted');
        $this->assertLessThan($lockIndex, $beginIndex, 'The transaction must begin before the lock');
        $this->assertLessThan($countIndex, $lockIndex, 'The lock must be taken before the limit is counted');
        $this->assertLessThan($insertIndex, $countIndex, 'The limit must be counted before the insert');
    }

    /**
     * @param list<array{sql: string, bindings: array<int, mixed>}>           $activity
     * @param callable(array{sql: string, bindings: array<int, mixed>}): bool $matches
     */
    private function findActivityIndex(array $activity, callable $matches): ?int
    {
        foreach ($activity as $index => $entry) {
            if ($matches($entry)) {
                return $index;
            }
        }

        return null;
    }
}
