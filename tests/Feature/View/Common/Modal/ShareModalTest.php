<?php

namespace Tests\Feature\View\Common\Modal;

use App\Models\DungeonRoute\DungeonRoute;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('ShareModal')]
final class ShareModalTest extends PublicTestCase
{
    #[Test]
    public function render_givenLinkSection_returnsIncludeLocationCheckboxAsFormCheckInput(): void
    {
        // Arrange
        $dungeonRoute = null;

        try {
            $dungeonRoute = DungeonRoute::factory()->create(['expires_at' => null]);

            // Act
            $xpath = $this->renderShareModal($dungeonRoute);

            // Assert
            $checkbox = $xpath->query('//input[@id="map_include_location_checkbox"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $checkbox);
            $this->assertSame('checkbox', $checkbox->getAttribute('type'));
            $classes = explode(' ', $checkbox->getAttribute('class'));
            $this->assertContains('form-check-input', $classes);
            $this->assertNotContains('form-control', $classes, 'form-control hides the checked state of a checkbox');
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function render_givenLinkSection_returnsIncludeLocationLabelBoundToCheckbox(): void
    {
        // Arrange
        $dungeonRoute = null;

        try {
            $dungeonRoute = DungeonRoute::factory()->create(['expires_at' => null]);

            // Act
            $xpath = $this->renderShareModal($dungeonRoute);

            // Assert
            $labels = $xpath->query('//label[@for="map_include_location_checkbox"]');
            $this->assertSame(1, $labels->length);
            $this->assertSame(0, $xpath->query('//label[@for="map_include_location"]')->length);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    private function renderShareModal(DungeonRoute $dungeonRoute): DOMXPath
    {
        $html = view('common.modal.share', [
            'dungeonroute' => $dungeonRoute,
            'show'         => ['publish' => false, 'embed' => false, 'mdt-export' => false],
        ])->render();

        $document = new DOMDocument();
        @$document->loadHTML(sprintf('<?xml encoding="UTF-8"><body>%s</body>', $html));

        return new DOMXPath($document);
    }
}
