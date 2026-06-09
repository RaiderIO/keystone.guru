<?php

namespace Tests\Unit\App\Rules;

use App\Rules\JsonStringCountRule;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Rules')]
final class JsonStringCountRuleTest extends TestCase
{
    #[Test]
    public function validate_givenValidJsonAboveMinCount_passes(): void
    {
        // Arrange
        $rule   = new JsonStringCountRule(minCount: 2);
        $failed = false;

        // Act
        $rule->validate('attr', json_encode([1, 2, 3]), function () use (&$failed) {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed);
    }

    #[Test]
    public function validate_givenValidJsonAtMinCount_passes(): void
    {
        // Arrange
        $rule   = new JsonStringCountRule(minCount: 2);
        $failed = false;

        // Act
        $rule->validate('attr', json_encode([1, 2]), function () use (&$failed) {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed);
    }

    #[Test]
    public function validate_givenValidJsonBelowMinCount_failsWithMinMessage(): void
    {
        // Arrange
        $rule    = new JsonStringCountRule(minCount: 2);
        $message = null;

        // Act
        $rule->validate('attr', json_encode([1]), function (string $msg) use (&$message) {
            $message = $msg;
        });

        // Assert
        $this->assertNotNull($message);
        $this->assertStringContainsString('2', $message);
    }

    #[Test]
    public function validate_givenInvalidJsonString_failsWithMinMessage(): void
    {
        // Arrange
        $rule    = new JsonStringCountRule(minCount: 2);
        $message = null;

        // Act
        $rule->validate('attr', 'not-valid-json', function (string $msg) use (&$message) {
            $message = $msg;
        });

        // Assert
        $this->assertNotNull($message);
    }

    #[Test]
    public function validate_givenValidJsonAboveMaxCount_failsWithMaxMessage(): void
    {
        // Arrange
        $rule    = new JsonStringCountRule(minCount: 2, maxCount: 2);
        $message = null;

        // Act
        $rule->validate('attr', json_encode([1, 2, 3]), function (string $msg) use (&$message) {
            $message = $msg;
        });

        // Assert
        $this->assertNotNull($message);
        $this->assertStringContainsString('2', $message);
    }

    #[Test]
    public function validate_givenValidJsonAtMaxCount_passes(): void
    {
        // Arrange
        $rule   = new JsonStringCountRule(minCount: 2, maxCount: 2);
        $failed = false;

        // Act
        $rule->validate('attr', json_encode([1, 2]), function () use (&$failed) {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed);
    }

    #[Test]
    public function validate_givenNoMaxCount_doesNotFailWhenAboveMinCount(): void
    {
        // Arrange
        $rule   = new JsonStringCountRule(minCount: 2);
        $failed = false;

        // Act
        $rule->validate('attr', json_encode([1, 2, 3, 4, 5]), function () use (&$failed) {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed);
    }
}
