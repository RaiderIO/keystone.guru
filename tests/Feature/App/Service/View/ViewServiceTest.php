<?php

namespace App\Service\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Feature\Traits\LoadsJsonFiles;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;
use Throwable;

final class ViewServiceTest extends PublicTestCase
{
    use LoadsJsonFiles;

    /**
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    #[Group('ViewService')]
    #[DataProvider('shouldLoadViewVariables_GivenWhitelistedUrls_ShouldReturnTrue_dataProvider')]
    public function shouldLoadViewVariables_GivenWhitelistedUrls_ShouldReturnTrue(
        string $uri,
    ): void {
        // Arrange
        $viewService = ServiceFixtures::getViewServiceMock(
            testCase: $this,
        );

        // Act
        $result = $viewService->shouldLoadViewVariables($uri);

        // Assert
        $this->assertTrue($result);
    }

    public static function shouldLoadViewVariables_GivenWhitelistedUrls_ShouldReturnTrue_dataProvider(): \Generator
    {
        // Whitelisted
        yield ['/ajax/search'];
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    #[Group('ViewService')]
    #[DataProvider('shouldLoadViewVariables_GivenBlacklistedUrls_ShouldReturnFalse_dataProvider')]
    public function shouldLoadViewVariables_GivenBlacklistedUrls_ShouldReturnFalse(
        string $uri,
    ): void {
        // Arrange
        $viewService = ServiceFixtures::getViewServiceMock(
            testCase: $this,
        );

        // Act
        $result = $viewService->shouldLoadViewVariables($uri);

        // Assert
        $this->assertFalse($result);
    }

    public static function shouldLoadViewVariables_GivenBlacklistedUrls_ShouldReturnFalse_dataProvider(): \Generator
    {
        yield ['/ajax/brushline'];
        yield ['/ajax/route'];

        yield ['/api/v1/dungeon'];
        yield ['/api/v1/route'];

        yield ['/benchmark'];
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    #[Group('ViewService')]
    #[DataProvider('shouldLoadViewVariables_GivenValidViewUrls_ShouldReturnTrue_dataProvider')]
    public function shouldLoadViewVariables_GivenValidViewUrls_ShouldReturnTrue(
        string $uri,
    ): void {
        // Arrange
        $viewService = ServiceFixtures::getViewServiceMock(
            testCase: $this,
        );

        // Act
        $result = $viewService->shouldLoadViewVariables($uri);

        // Assert
        $this->assertTrue($result);
    }

    public static function shouldLoadViewVariables_GivenValidViewUrls_ShouldReturnTrue_dataProvider(): \Generator
    {
        yield ['/api'];
        yield ['/api.zip'];
        yield ['/apis/controllers/users.js'];
        yield ['/apis/config/config.js'];
        yield ['/api_keys.json'];
        yield ['/api_keys.yml'];
        yield ['/api_keys/sendgrid_keys.json'];
        yield ['/'];
    }
}
