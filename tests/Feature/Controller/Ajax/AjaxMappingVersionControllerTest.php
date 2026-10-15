<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('MappingVersion')]
final class AjaxMappingVersionControllerTest extends AjaxPublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function store_givenATimer_updatesIt(): void
    {
        // Arrange
        $mappingVersion = $this->createMappingVersion();

        // Act
        $response = $this->patchJson(sprintf('/ajax/admin/mappingVersion/%d', $mappingVersion->id), [
            'timer_max_seconds' => 1800,
            'facade_enabled'    => 1,
        ]);

        // Assert
        $response->assertOk();
        $mappingVersion->refresh();
        $this->assertSame(1800, $mappingVersion->timer_max_seconds);
        $this->assertTrue((bool)$mappingVersion->facade_enabled);
    }

    /**
     * @param array<string, string> $payload
     */
    #[Test]
    #[DataProvider('emptyRequiredFieldProvider')]
    public function store_givenAnEmptyRequiredField_returnsValidationErrorAndKeepsTheMappingVersion(array $payload, string $expectedErrorKey): void
    {
        // Arrange - clearing both the minutes and the seconds input submits an empty timer
        $mappingVersion = $this->createMappingVersion();

        // Act
        $response = $this->patchJson(sprintf('/ajax/admin/mappingVersion/%d', $mappingVersion->id), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([$expectedErrorKey]);
        $mappingVersion->refresh();
        $this->assertSame(1200, $mappingVersion->timer_max_seconds);
        $this->assertFalse((bool)$mappingVersion->facade_enabled);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function emptyRequiredFieldProvider(): array
    {
        return [
            'timer'          => [['timer_max_minutes' => '', 'timer_max_seconds' => ''], 'timer_max_seconds'],
            'facade enabled' => [['facade_enabled' => ''], 'facade_enabled'],
        ];
    }

    private function createMappingVersion(): MappingVersion
    {
        $dungeon = $this->createDungeon(mappingVersionAttributes: [
            'timer_max_seconds' => 1200,
            'facade_enabled'    => false,
        ]);

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->mappingVersions()->firstOrFail();

        return $mappingVersion;
    }
}
