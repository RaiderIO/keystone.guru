<?php

namespace Tests\Traits;

use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\CombatLog\ParsedCombatLog;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Patreon\PatreonBenefit;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\SeasonDungeon;
use App\Models\Spell\Spell;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Event\Facade as EventFacade;

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
     * @var array<string, array<int, class-string>>
     */
    private const array LEAK_GUARD_COUNTED_TABLES = [
        'phpunit' => [
            User::class, Team::class, DungeonRoute::class, DungeonRouteThumbnail::class, Season::class,
            SeasonDungeon::class, Dungeon::class, MappingVersion::class, Expansion::class, GameVersion::class,
            GameServerRegion::class, Floor::class, AffixGroup::class, Role::class, Tag::class, TagCategory::class,
            PatreonBenefit::class, MapIconType::class, PublishedState::class, Npc::class,
        ],
        'combatlog' => [
            ChallengeModeRun::class, ChallengeModeRunData::class, CombatLogRouteEnemyFailure::class,
            CombatLogNpcEvent::class, CombatLogSpellEvent::class, ParsedCombatLog::class,
        ],
    ];

    /**
     * Too large to count in full on every test; only rows above the id the process started with are counted, so a
     * row a test inserts still shows while the tens of thousands of seeded rows cost a single index range scan.
     *
     * @var array<string, array<int, class-string>>
     */
    private const array LEAK_GUARD_APPENDED_TABLES = [
        'phpunit' => [Enemy::class, EnemyPack::class, Spell::class, NpcEnemyForces::class],
    ];

    /**
     * Seeded rows a test may flip rather than create, counted by the state a fresh seed never has: label => the
     * model and the where clause its rows are counted by.
     *
     * @var array<string, array<string, array{0: class-string, 1: string}>>
     */
    private const array LEAK_GUARD_PREDICATES = [
        'phpunit' => [
            'dungeons where active = 0'    => [Dungeon::class, '`active` = 0'],
            'seasons parked at 2999-01-01' => [Season::class, '`start` >= \'2999-01-01\''],
        ],
    ];

    /** @var array<string, int> Per process: the highest id each appended table had when the first test ran */
    private static array $leakGuardBaselineIds = [];

    /** @var array<class-string<Model>, string> */
    private static array $leakGuardTableNames = [];

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

        foreach (self::LEAK_GUARD_APPENDED_TABLES as $connection => $modelClasses) {
            foreach ($modelClasses as $modelClass) {
                $table = self::leakGuardTableName($modelClass);
                $key   = sprintf('%s.%s', $connection, $table);
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

        EventFacade::emitter()->testTriggeredPhpunitWarning($this->valueObjectForEvents(), $message);
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

            foreach (self::LEAK_GUARD_COUNTED_TABLES[$connection] ?? [] as $modelClass) {
                $table = self::leakGuardTableName($modelClass);

                $columns[sprintf('%s.%s', $connection, $table)] = sprintf('(select count(*) from `%s`)', $table);
            }

            foreach (self::LEAK_GUARD_APPENDED_TABLES[$connection] ?? [] as $modelClass) {
                $table         = self::leakGuardTableName($modelClass);
                $key           = sprintf('%s.%s', $connection, $table);
                $columns[$key] = sprintf('(select count(*) from `%s` where `id` > %d)', $table, self::$leakGuardBaselineIds[$key]);
            }

            foreach (self::LEAK_GUARD_PREDICATES[$connection] ?? [] as $label => [$modelClass, $where]) {
                $columns[sprintf('%s: %s', $connection, $label)] = sprintf(
                    '(select count(*) from `%s` where %s)',
                    self::leakGuardTableName($modelClass),
                    $where,
                );
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

    /**
     * @param class-string<Model> $modelClass
     */
    private static function leakGuardTableName(string $modelClass): string
    {
        return self::$leakGuardTableNames[$modelClass] ??= new $modelClass()->getTable();
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
