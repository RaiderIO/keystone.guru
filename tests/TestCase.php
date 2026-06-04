<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
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
