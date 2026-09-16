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

    /**
     * @return array<int, mixed>
     */
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
            [
                '<a href="#anchor">Anchor</a>',
                '<a href="#anchor">Anchor</a>',
            ],
            [
                '<a href="HTTPS://Raider.IO/weekly-route">Uppercase</a>',
                '<a href="HTTPS://Raider.IO/weekly-route">Uppercase</a>',
            ],
            [
                '<a href="javascript:doSomething()">Scheme without a host</a>',
                'Scheme without a host',
            ],
            [
                '<a href="JaVaScRiPt:doSomething()">Mixed case scheme</a>',
                'Mixed case scheme',
            ],
            [
                "<a href=\"java\tscript:doSomething()\">Scheme with an embedded tab</a>",
                'Scheme with an embedded tab',
            ],
            [
                '<a href="data:text/html,Test">Data scheme</a>',
                'Data scheme',
            ],
            [
                '<a href="mailto:someone@example.com">Mail scheme</a>',
                'Mail scheme',
            ],
            [
                '<a href="//google.com/test">Protocol relative</a>',
                'Protocol relative',
            ],
            [
                '<a href="\\\\google.com/test">Leading backslashes</a>',
                'Leading backslashes',
            ],
            [
                '<a href="ftp://keystone.guru/test">Disallowed scheme on an allowed domain</a>',
                'Disallowed scheme on an allowed domain',
            ],
        ];
    }

    #[Test]
    #[Group('HtmlSanitizer')]
    #[DataProvider('stripAllTags_dataProvider')]
    public function stripAllTags_givenInput_returnsTextWithoutTags(?string $input, ?string $expected): void
    {
        // Arrange
        $sanitizer = new HtmlSanitizer();

        // Act
        $result = $sanitizer->stripAllTags($input);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function stripAllTags_dataProvider(): array
    {
        return [
            'null'                                  => [null, null],
            'empty'                                 => ['', ''],
            'plain text'                            => ['Pull the pack left of the boss', 'Pull the pack left of the boss'],
            'plain text with line breaks'           => ["Line 1\nLine 2", "Line 1\nLine 2"],
            'unicode'                               => ['Überpull — 50% 💀', 'Überpull — 50% 💀'],
            'bold'                                  => ['<b>x</b>', 'x'],
            'image with an event handler'           => ['<img src=x onerror=alert(1)>', ''],
            'script keeps its text inert'           => ['<script>alert(1)</script>', 'alert(1)'],
            'allowed link'                          => ['<a href="https://raider.io">Raider.IO</a>', 'Raider.IO'],
            'attribute value containing a bracket'  => ['<img alt=">" src=x>after', '" src=x>after'],
            'comment'                               => ['before<!-- hidden -->after', 'beforeafter'],
            'uppercase tag'                         => ['<SCRIPT>x</SCRIPT>', 'x'],
            'unterminated tag'                      => ['text<img src=x onerror=alert(1)', 'text'],
            'tag rebuilt by stripping another'      => ['<<b>script>alert(1)<</b>/script>', 'alert(1)'],
            'less than with spaces'                 => ['a < b', 'a < b'],
            'less than without spaces before digit' => ['I <3 this route', 'I <3 this route'],
            'less than or equal'                    => ['x <= 5', 'x <= 5'],
            'greater than'                          => ['a > b', 'a > b'],
            'escaped markup stays escaped'          => ['&lt;b&gt;x&lt;/b&gt;', '&lt;b&gt;x&lt;/b&gt;'],
        ];
    }
}
