<?php

namespace Tests\Feature\View\Common\Dungeon;

use App\Models\Dungeon;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('GridCards')]
final class GridCardsTest extends PublicTestCase
{
    private const string PUG_FRIENDLY_HREF = '/route/test/pug-friendly';
    private const string EXPERT_HREF       = '/route/test/expert';

    #[Test]
    public function render_givenLinks_neverHidesALinkAtAnyBreakpoint(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->firstOrFail();

        // Act
        $xpath = $this->renderGridCards($dungeon, [
            ['href' => self::PUG_FRIENDLY_HREF, 'text' => 'Pug friendly'],
            ['href' => self::EXPERT_HREF, 'text' => 'Expert'],
        ]);

        // Assert
        foreach ([self::PUG_FRIENDLY_HREF, self::EXPERT_HREF] as $href) {
            $this->assertSame(1, $xpath->query(sprintf('//a[@href="%s"]', $href))->length);
            $this->assertSame(
                0,
                $xpath->query(sprintf('//a[@href="%s"]/ancestor-or-self::*[%s]', $href, $this->hasClass('d-none')))->length,
                sprintf('The link to %s sits inside an element that is hidden below a breakpoint', $href),
            );
        }
    }

    #[Test]
    public function render_givenTwoLinks_separatesThemWithADotOnlyFromLgUp(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->firstOrFail();

        // Act
        $xpath = $this->renderGridCards($dungeon, [
            ['href' => self::PUG_FRIENDLY_HREF, 'text' => 'Pug friendly'],
            ['href' => self::EXPERT_HREF, 'text' => 'Expert'],
        ]);

        // Assert
        $separators = $xpath->query(sprintf('//*[%s][%s]', $this->hasClass('d-none'), $this->hasClass('d-lg-inline')));
        $this->assertSame(1, $separators->length);
        $this->assertSame('·', trim($separators->item(0)->textContent));
        $this->assertSame(
            1,
            $xpath->query(sprintf('//a[@href="%s"]/following-sibling::*[1][%s]', self::PUG_FRIENDLY_HREF, $this->hasClass('d-none')))->length,
            'The separator sits between the two links',
        );
    }

    #[Test]
    public function render_givenOneLink_rendersNoSeparator(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->firstOrFail();

        // Act
        $xpath = $this->renderGridCards($dungeon, [
            ['href' => self::EXPERT_HREF, 'text' => 'Expert'],
        ]);

        // Assert
        $this->assertSame(1, $xpath->query(sprintf('//a[@href="%s"]', self::EXPERT_HREF))->length);
        $this->assertStringNotContainsString('·', $xpath->document->textContent);
    }

    /**
     * @param list<array{href: string, text: string}> $links
     */
    private function renderGridCards(Dungeon $dungeon, array $links): DOMXPath
    {
        $html = view('common.dungeon.gridcards', [
            'dungeons'        => collect([$dungeon]),
            'colCount'        => 12,
            'useAbbreviation' => true,
            'links'           => collect([$dungeon->key => collect($links)]),
            'imageLinks'      => [$dungeon->key => '/discover/test'],
        ])->render();

        $document = new DOMDocument();
        @$document->loadHTML(sprintf('<?xml encoding="UTF-8">%s', $html));

        return new DOMXPath($document);
    }

    private function hasClass(string $class): string
    {
        return sprintf('contains(concat(" ", normalize-space(@class), " "), " %s ")', $class);
    }
}
