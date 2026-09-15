<?php

namespace Tests\Feature\Controller;

use Illuminate\Support\Facades\Cache;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
final class JavascriptControllerTest extends PublicTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // RemembersToFile writes to the `tmp_file` file store, which survives between test runs.
        Cache::store('tmp_file')->flush();
    }

    #[Test]
    public function mapContextStaticData_givenAllowedLocale_returnsOk(): void
    {
        // Act
        $response = $this->get(route('js.mapcontext.static', ['locale' => 'en_US']));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function mapContextStaticData_givenDisallowedLocale_returnsNotFound(): void
    {
        // Act - the {locale} route parameter is not otherwise validated, so an arbitrary string
        // must not be allowed to mint its own cache entry (or reach the raw locale SQL comparison
        // in MapContextStaticData::toArray()).
        $response = $this->get(route('js.mapcontext.static', ['locale' => 'not_a_real_locale']));

        // Assert
        $response->assertNotFound();
    }
}
