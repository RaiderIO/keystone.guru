<?php

namespace Tests\Feature\Rules;

use App\Rules\RelativeUrlRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Rules')]
#[Group('Notification')]
final class RelativeUrlRuleTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('urlProvider')]
    public function isRelativeUrl_givenUrl_returnsWhetherItStaysOnThisSite(?string $url, bool $expected): void
    {
        // Act
        $isRelative = RelativeUrlRule::isRelativeUrl($url);

        // Assert
        self::assertSame($expected, $isRelative, sprintf('Unexpected verdict for %s', var_export($url, true)));
    }

    /**
     * @return array<string, array{0: string|null, 1: bool}>
     */
    public static function urlProvider(): array
    {
        return [
            'a path'              => ['/discover', true],
            'the root'            => ['/', true],
            'a path with a query' => ['/discover?foo=bar', true],
            // A browser reads these as an authority, not a path, so they leave the site
            'protocol relative'           => ['//evil.example.com', false],
            'protocol relative with path' => ['//evil.example.com/phish', false],
            'backslash authority'         => ['/\\evil.example.com', false],
            'absolute http'               => ['http://evil.example.com', false],
            'absolute https'              => ['https://evil.example.com', false],
            'scheme relative to nothing'  => ['evil.example.com', false],
            'empty'                       => ['', false],
            'null'                        => [null, false],
        ];
    }
}
