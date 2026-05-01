<?php

namespace Tests\Unit\App\Logic\Utils;

use App\Logic\Utils\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    #[Test]
    #[Group('HtmlSanitizer')]
    #[DataProvider('sanitize_dataProvider')]
    public function sanitize_shouldSanitizeHtml(string $input, string $expected): void
    {
        // Arrange
        $sanitizer = new HtmlSanitizer();

        // Act
        $result = $sanitizer->sanitize($input);

        // Assert
        $this->assertEquals($expected, $result);
    }

    public static function sanitize_dataProvider(): array
    {
        return [
            [
                'Click me<br><a href="https://raider.io/weekly-route" onclick="alert(\'xss\');">Click me</a>',
                'Click me<br><a href="https://raider.io/weekly-route">Click me</a>',
            ],
            [
                'Click me<br><a href="https://google.com" onclick="alert(\'xss\');">Click me</a>',
                'Click me<br>Click me',
            ],
            [
                '<h4>Title</h4><script>alert("xss");</script><p>Paragraph</p>',
                '<h4>Title</h4>alert("xss");Paragraph',
            ],
            [
                '<b>Bold</b><i>Italic</i><u>Underline</u>',
                '<b>Bold</b><i>Italic</i>Underline',
            ],
            [
                "Line 1\nLine 2",
                'Line 1<br>Line 2',
            ],
            [
                '<h6>Small Title</h6>',
                '<h6>Small Title</h6>',
            ],
            [
                '<a href="https://keystone.guru">Keystone.guru</a>',
                '<a href="https://keystone.guru">Keystone.guru</a>',
            ],
            [
                '<a href="/test">Relative</a>',
                '<a href="/test">Relative</a>',
            ],
        ];
    }
}
