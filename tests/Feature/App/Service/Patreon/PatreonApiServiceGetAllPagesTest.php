<?php

namespace Tests\Feature\App\Service\Patreon;

use App\Service\Patreon\Dtos\PatreonPagedResponse;
use App\Service\Patreon\Logging\PatreonApiServiceLoggingInterface;
use App\Service\Patreon\PatreonApiService;
use Patreon\API;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCases\PublicTestCase;

/**
 * The pagination walk behind every campaign request.
 *
 * The behaviour under test is #4373's core defect: a page that failed after the first one used to
 * produce a clean-looking partial response, which the sync then treated as the complete campaign.
 */
#[Group('Patreon')]
#[Group('PatreonApiService')]
final class PatreonApiServiceGetAllPagesTest extends PublicTestCase
{
    #[Test]
    public function getAllPages_givenMultipleCleanPages_mergesDataAndIncludedFromEveryPage(): void
    {
        // Arrange
        $apiClient = $this->createMockPublic(API::class);
        $apiClient->method('get_data')->willReturnOnConsecutiveCalls(
            [
                'data'     => [['id' => '1', 'type' => 'member']],
                'included' => [['id' => 't1', 'type' => 'tier']],
                'links'    => ['next' => 'https://patreon.test/next'],
                'meta'     => ['pagination' => ['cursors' => ['next' => 'cursor-2']]],
            ],
            [
                'data'     => [['id' => '2', 'type' => 'member']],
                'included' => [['id' => 't2', 'type' => 'tier']],
            ],
        );

        // Act
        $result = $this->getAllPages($apiClient);

        // Assert
        $this->assertFalse($result->truncated);
        $this->assertFalse($result->hasErrors());
        $this->assertSame(2, $result->pageCount);
        $this->assertSame(2, $result->rowCount);
        $this->assertSame(['1', '2'], array_column($result->response['data'], 'id'));
        // A campaign's tiers live in `included` - keeping only the last page's would make an entitled
        // tier unresolvable, which silently revokes benefits
        $this->assertSame(['t1', 't2'], array_column($result->response['included'], 'id'));
    }

    #[Test]
    public function getAllPages_givenAnUndecodablePageAfterTheFirst_reportsErrorsInsteadOfACleanPartial(): void
    {
        // Arrange - Patreon serving an HTML error page rather than JSON, mid-pagination
        $apiClient = $this->createMockPublic(API::class);
        $apiClient->method('get_data')->willReturnOnConsecutiveCalls(
            [
                'data'  => [['id' => '1', 'type' => 'member']],
                'links' => ['next' => 'https://patreon.test/next'],
                'meta'  => ['pagination' => ['cursors' => ['next' => 'cursor-2']]],
            ],
            '<html><body>502 Bad Gateway</body></html>',
        );

        // Act
        $result = $this->getAllPages($apiClient);

        // Assert - the partial page set must never be mistakable for the complete campaign
        $this->assertTrue($result->truncated);
        $this->assertTrue($result->hasErrors());
        $this->assertSame(1, $result->rowCount);
    }

    #[Test]
    public function getAllPages_givenAPageCarryingErrors_stopsAndKeepsTheErrors(): void
    {
        // Arrange
        $apiClient = $this->createMockPublic(API::class);
        $apiClient->method('get_data')->willReturnOnConsecutiveCalls(
            [
                'data'  => [['id' => '1', 'type' => 'member']],
                'links' => ['next' => 'https://patreon.test/next'],
                'meta'  => ['pagination' => ['cursors' => ['next' => 'cursor-2']]],
            ],
            ['errors' => [['detail' => 'Rate limited']]],
        );

        // Act
        $result = $this->getAllPages($apiClient);

        // Assert
        $this->assertTrue($result->truncated);
        $this->assertTrue($result->hasErrors());
    }

    #[Test]
    public function getAllPages_givenANextLinkWithoutACursor_treatsItAsTruncated(): void
    {
        // Arrange - a next page we are told about but cannot ask for is missing data just the same
        $apiClient = $this->createMockPublic(API::class);
        $apiClient->method('get_data')->willReturn([
            'data'  => [['id' => '1', 'type' => 'member']],
            'links' => ['next' => 'https://patreon.test/next'],
            'meta'  => ['pagination' => ['cursors' => []]],
        ]);

        // Act
        $result = $this->getAllPages($apiClient);

        // Assert
        $this->assertTrue($result->truncated);
        $this->assertTrue($result->hasErrors());
    }

    #[Test]
    public function getAllPages_givenASinglePageOfResults_reportsNoTruncation(): void
    {
        // Arrange
        $apiClient = $this->createMockPublic(API::class);
        $apiClient->method('get_data')->willReturn(['data' => [['id' => '1', 'type' => 'member']]]);

        // Act
        $result = $this->getAllPages($apiClient);

        // Assert
        $this->assertFalse($result->truncated);
        $this->assertFalse($result->hasErrors());
        $this->assertSame(1, $result->pageCount);
    }

    private function getAllPages(API $apiClient): PatreonPagedResponse
    {
        $patreonApiService = new PatreonApiService($this->createMockPublic(PatreonApiServiceLoggingInterface::class));

        $method = new ReflectionMethod($patreonApiService, 'getAllPages');

        /** @var PatreonPagedResponse $result */
        $result = $method->invoke($patreonApiService, $apiClient, 'campaigns/1/members?include=currently_entitled_tiers');

        return $result;
    }
}
