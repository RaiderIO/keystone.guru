<?php

namespace Tests;

use App\Logging\StructuredLogging;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Event;
use Tests\Attributes\Repeat;
use Tests\Attributes\SlowTest;

abstract class TestCase extends BaseTestCase
{
    use Bootstrap;
    use Shutdown;

    private const float WARN_TEST_DURATION_SECONDS = 1.0;

    private const float MAX_TEST_DURATION_SECONDS = 10.0;

    private float $testStartTime;

    #[\Override]
    protected function assertPreConditions(): void
    {
        parent::assertPreConditions();

        try {
            $methodReflector  = new \ReflectionMethod($this, $this->name());
            $repeatAttributes = $methodReflector->getAttributes(Repeat::class);
        } catch (\ReflectionException) {
            return;
        }

        if (empty($repeatAttributes)) {
            return;
        }

        /** @var Repeat $repeat */
        $repeat     = $repeatAttributes[0]->newInstance();
        $methodName = $this->name();
        $args       = $this->providedData();

        // Run N-1 extra iterations here; runTest() provides the final one.
        for ($i = 0; $i < $repeat->times - 1; $i++) {
            $this->{$methodName}(...$args);
        }
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->testStartTime = microtime(true);

        // The combatlog connection has no phpunit variant of its own, so in the main checkout
        // tests historically read/wrote the LIVE combatlog schema - whose combat-log-derived rows
        // are not recoverable (#4346). When a dedicated test schema is configured
        // (DB_PHPUNIT_COMBATLOG_DATABASE -> the combatlog_phpunit connection), redirect the
        // combatlog connection to it before any test touches it. Per-test on purpose: the app is
        // rebuilt (and config reset) for every test.
        $phpunitCombatlogDatabase = config('database.connections.combatlog_phpunit.database');
        if (!empty($phpunitCombatlogDatabase)) {
            // url must go too: a configured DB_URL would re-override `database` when the
            // connection is built, silently undoing the isolation
            config([
                'database.connections.combatlog.database' => $phpunitCombatlogDatabase,
                'database.connections.combatlog.url'      => null,
            ]);
            DB::purge('combatlog');
        }

        // StructuredLogging caches config values in statics that survive across tests - a config() change made by a
        // previous test must not leak into this one. enable() is used instead of flushConfigCache() alone because
        // it also resets the $ENABLED latch: a disable() left unwound by some other test would otherwise silence
        // structured logging for the rest of the shard, and the CI leak-guard would then pass for the wrong reason.
        StructuredLogging::enable();

        // An explicit channel is the one input resolveChannel() honours before any of its "am I running tests"
        // checks, so a setChannel() a previous test failed to unwind would send every later test's log lines to
        // that channel - the #3782 leak again, through the one door the guard there cannot close. Not folded into
        // flushConfigCache() because that is also called from StructuredLogging::enable(), where clearing a
        // deliberately-set channel would be wrong.
        StructuredLogging::setChannel(null);

        // Use a hacky global so that we really only execute this once
        global $initialized;

        if (!$initialized) {
            // Do something once here for _all_ test subclasses.
            $initialized = true;

            $this->bootstrap();
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        $elapsed = microtime(true) - $this->testStartTime;

        if ($this->isExcludedFromTimingCheck()) {
            parent::tearDown();

            return;
        }

        parent::tearDown();

        if ($elapsed > self::MAX_TEST_DURATION_SECONDS) {
            $this->fail(sprintf(
                'Test took %.2fs, exceeding the %.1fs hard limit.',
                $elapsed,
                self::MAX_TEST_DURATION_SECONDS,
            ));
        } elseif ($elapsed > self::WARN_TEST_DURATION_SECONDS) {
            Event\Facade::emitter()->testTriggeredPhpunitWarning(
                $this->valueObjectForEvents(),
                sprintf(
                    'Test took %.2fs, which exceeds the %.1fs soft limit.',
                    $elapsed,
                    self::WARN_TEST_DURATION_SECONDS,
                ),
            );
        }
    }

    private function isExcludedFromTimingCheck(): bool
    {
        if (!config('app.debug')) {
            return true;
        }

        $classReflector = new \ReflectionClass($this);

        if (!empty($classReflector->getAttributes(SlowTest::class))) {
            return true;
        }

        try {
            $methodReflector = new \ReflectionMethod($this, $this->name());
            if (!empty($methodReflector->getAttributes(SlowTest::class))) {
                return true;
            }
            if (!empty($methodReflector->getAttributes(Repeat::class))) {
                return true;
            }
        } catch (\ReflectionException) {
            // name() may include data provider suffixes — ignore
        }

        return false;
    }

    //    protected function tearDown(): void
    //    {
    //        parent::tearDown();
    //
    //        global $initialized;
    //
    //        if ($initialized) {
    //            $initialized = false;
    //
    //            $this->shutdown();
    //        }
    //    }
}
