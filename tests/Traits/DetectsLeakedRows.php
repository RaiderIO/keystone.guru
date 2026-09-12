<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Event;

/**
 * Compares the row counts of the tables tests most often pollute before and after every test, and reports the
 * test that left a difference behind. The test database persists between tests and between runs, so a row a test
 * does not delete is read by every later test as if it were seeded.
 *
 * TEST_LEAK_GUARD selects what a difference does: `fail` (the default) fails the test, `warn` attaches a PHPUnit
 * warning to it instead, `off` skips the check.
 */
trait DetectsLeakedRows
{
    private const string LEAK_GUARD_OFF = 'off';

    private const string LEAK_GUARD_WARN = 'warn';

    private const string LEAK_GUARD_FAIL = 'fail';

    /**
     * Counted in full. Every table here is small enough that the count is a fraction of a millisecond.
     *
     * @var array<string, array<int, string>>
     */
    private const array LEAK_GUARD_COUNTED_TABLES = [
        'phpunit' => [
            'users', 'teams', 'dungeon_routes', 'dungeon_route_thumbnails', 'seasons', 'season_dungeons', 'dungeons',
            'mapping_versions', 'expansions', 'game_versions', 'game_server_regions', 'floors', 'affix_groups',
            'roles', 'tags', 'tag_categories', 'patreon_benefits', 'map_icon_types', 'published_states', 'npcs',
        ],
        'combatlog' => [
            'challenge_mode_runs', 'challenge_mode_run_data', 'combat_log_route_enemy_failures',
            'combat_log_npc_events', 'combat_log_spell_events', 'parsed_combat_logs',
        ],
    ];

    /**
     * Too large to count in full on every test; only rows above the id the process started with are counted, so a
     * row a test inserts still shows while the tens of thousands of seeded rows cost a single index range scan.
     *
     * @var array<string, array<int, string>>
     */
    private const array LEAK_GUARD_APPENDED_TABLES = [
        'phpunit' => ['enemies', 'enemy_packs', 'spells', 'npc_enemy_forces'],
    ];

    /**
     * Seeded rows a test may flip rather than create, counted by the state a fresh seed never has.
     *
     * @var array<string, array<string, string>>
     */
    private const array LEAK_GUARD_PREDICATES = [
        'phpunit' => [
            'dungeons where active = 0'    => 'select count(*) from `dungeons` where `active` = 0',
            'seasons parked at 2999-01-01' => 'select count(*) from `seasons` where `start` >= \'2999-01-01\'',
        ],
    ];

    /** @var array<string, int> Per process: the highest id each appended table had when the first test ran */
    private static array $leakGuardBaselineIds = [];

    /** @var array<string, PDO> Held across tearDown(), where the application (and DB manager) is already gone */
    private array $leakGuardConnections = [];

    /** @var array<string, int> */
    private array $leakGuardCountsBefore = [];

    private string $leakGuardMode = self::LEAK_GUARD_FAIL;

    /**
     * Call at the end of setUp(), once every connection points at its test schema.
     */
    protected function snapshotRowCountsForLeakGuard(): void
    {
        $this->leakGuardMode = self::resolveLeakGuardMode();
        if ($this->leakGuardMode === self::LEAK_GUARD_OFF) {
            return;
        }

        $this->leakGuardConnections = [];
        foreach (array_keys(self::LEAK_GUARD_COUNTED_TABLES) as $connection) {
            $this->leakGuardConnections[$connection] = DB::connection($connection)->getPdo();
        }

        foreach (self::LEAK_GUARD_APPENDED_TABLES as $connection => $tables) {
            foreach ($tables as $table) {
                $key = sprintf('%s.%s', $connection, $table);
                if (!array_key_exists($key, self::$leakGuardBaselineIds)) {
                    $maxId = $this->leakGuardConnections[$connection]
                        ->query(sprintf('select coalesce(max(`id`), 0) from `%s`', $table))
                        ->fetchColumn();

                    self::$leakGuardBaselineIds[$key] = (int)$maxId;
                }
            }
        }

        $this->leakGuardCountsBefore = $this->readRowCountsForLeakGuard();
    }

    /**
     * Call after parent::tearDown(): every cleanup a test registers, in a finally, its own tearDown() or a
     * beforeApplicationDestroyed() callback, has run by then.
     */
    protected function reportLeakedRows(): void
    {
        if ($this->leakGuardMode === self::LEAK_GUARD_OFF || $this->leakGuardCountsBefore === []) {
            return;
        }

        $differences = [];
        foreach ($this->readRowCountsForLeakGuard() as $key => $countAfter) {
            $delta = $countAfter - ($this->leakGuardCountsBefore[$key] ?? 0);
            if ($delta !== 0) {
                $differences[] = sprintf('%s %+d', $key, $delta);
            }
        }

        $this->leakGuardCountsBefore = [];
        $this->leakGuardConnections  = [];

        if ($differences === []) {
            return;
        }

        $message = sprintf(
            'Test left rows behind in the test database (before -> after): %s. Delete what the test created in a finally, and restore any seeded row it changed.',
            implode(', ', $differences),
        );

        if ($this->leakGuardMode === self::LEAK_GUARD_FAIL) {
            $this->fail($message);
        }

        Event\Facade::emitter()->testTriggeredPhpunitWarning($this->valueObjectForEvents(), $message);
    }

    /**
     * One round trip per connection: every count is a scalar subquery in the same select.
     *
     * @return array<string, int>
     */
    private function readRowCountsForLeakGuard(): array
    {
        $counts = [];

        foreach ($this->leakGuardConnections as $connection => $pdo) {
            $columns = [];

            foreach (self::LEAK_GUARD_COUNTED_TABLES[$connection] ?? [] as $table) {
                $columns[sprintf('%s.%s', $connection, $table)] = sprintf('(select count(*) from `%s`)', $table);
            }

            foreach (self::LEAK_GUARD_APPENDED_TABLES[$connection] ?? [] as $table) {
                $key           = sprintf('%s.%s', $connection, $table);
                $columns[$key] = sprintf('(select count(*) from `%s` where `id` > %d)', $table, self::$leakGuardBaselineIds[$key]);
            }

            foreach (self::LEAK_GUARD_PREDICATES[$connection] ?? [] as $label => $sql) {
                $columns[sprintf('%s: %s', $connection, $label)] = sprintf('(%s)', $sql);
            }

            $select = [];
            $index  = 0;
            foreach ($columns as $sql) {
                $select[] = sprintf('%s as `c%d`', $sql, $index++);
            }

            $row = $pdo->query(sprintf('select %s', implode(', ', $select)))->fetch(PDO::FETCH_NUM);

            $index = 0;
            foreach (array_keys($columns) as $key) {
                $counts[$key] = (int)$row[$index++];
            }
        }

        return $counts;
    }

    private static function resolveLeakGuardMode(): string
    {
        $mode = $_SERVER['TEST_LEAK_GUARD'] ?? $_ENV['TEST_LEAK_GUARD'] ?? getenv('TEST_LEAK_GUARD');
        $mode = is_string($mode) ? strtolower(trim($mode)) : '';

        return in_array($mode, [self::LEAK_GUARD_OFF, self::LEAK_GUARD_WARN, self::LEAK_GUARD_FAIL], true)
            ? $mode
            : self::LEAK_GUARD_FAIL;
    }
}
