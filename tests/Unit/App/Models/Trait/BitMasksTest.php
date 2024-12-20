<?php

namespace Tests\Unit\App\Models\Trait;

use App\Models\Traits\BitMasks;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BitMasksTest extends TestCase
{
    private $bitMask;

    protected function setUp(): void
    {
        // Create an anonymous class that uses the BitMasks trait
        $this->bitMask = new class {
            use BitMasks;

            // Expose protected methods for testing
            public function testBitMaskAdd(int $value, int $flag): int
            {
                return $this->bitMaskAdd($value, $flag);
            }

            public function testBitMaskRemove(int $value, int $flag): int
            {
                return $this->bitMaskRemove($value, $flag);
            }

            public function testBitMaskHasValue(int $value, int $flag): bool
            {
                return $this->bitMaskHasValue($value, $flag);
            }
        };
    }

    #[Test]
    #[Group('BitMasks')]
    public function testBitMaskAdd()
    {
        $this->assertEquals(6, $this->bitMask->testBitMaskAdd(4, 2));  // 4 | 2 = 6
        $this->assertEquals(5, $this->bitMask->testBitMaskAdd(5, 0));  // 5 | 0 = 5
    }

    #[Test]
    #[Group('BitMasks')]
    public function testBitMaskRemove()
    {
        $this->assertEquals(4, $this->bitMask->testBitMaskRemove(6, 2)); // 6 & ~2 = 4
        $this->assertEquals(1, $this->bitMask->testBitMaskRemove(3, 2)); // 3 & ~2 = 1
    }

    #[Test]
    #[Group('BitMasks')]
    public function testBitMaskHasValue()
    {
        $this->assertTrue($this->bitMask->testBitMaskHasValue(6, 2));   // 6 & 2 > 0
        $this->assertFalse($this->bitMask->testBitMaskHasValue(4, 2));  // 4 & 2 == 0
        $this->assertTrue($this->bitMask->testBitMaskHasValue(7, 1));   // 7 & 1 > 0
    }
}
