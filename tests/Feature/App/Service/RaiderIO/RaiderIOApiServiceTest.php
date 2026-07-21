<?php

namespace Tests\Feature\App\Service\RaiderIO;

use App\Models\Expansion;
use App\Models\Season;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\RaiderIO\Dtos\CombatLogSegmentsResponse;
use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use App\Service\RaiderIO\RaiderIOApiService;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('RaiderIO')]
#[Group('RaiderIOApiService')]
final class RaiderIOApiServiceTest extends PublicTestCase
{
    private const int RUN_ID        = 37830910;
    private const string ACCESS_KEY = 'test-access-key';

    /** @var MockObject&RaiderIOApiServiceLoggingInterface */
    private MockObject $log;

    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['keystoneguru.raiderio.api_key' => self::ACCESS_KEY]);

        $this->log = $this->createMockPublic(RaiderIOApiServiceLoggingInterface::class);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenValidResponse_buildsQueryUrlAndParsesSegments(): void
    {
        // Arrange
        $capturedUrl = null;
        $service     = $this->makeService(function (string $url) use (&$capturedUrl): string {
            $capturedUrl = $url;

            return json_encode([
                'sourceUserId' => 1348248,
                'segments'     => [
                    ['id' => 1, 'type' => 'trash', 'downloadUrl' => 'https://s3/01_trash.txt.gz'],
                    ['id' => 2, 'type' => 'boss', 'downloadUrl' => 'https://s3/02_boss.txt.gz'],
                ],
            ]);
        });

        // Act
        $result = $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertStringContainsString('season=season-mn-1', $capturedUrl);
        $this->assertStringContainsString(sprintf('keystone_run_id=%d', self::RUN_ID), $capturedUrl);
        $this->assertStringContainsString(sprintf('access_key=%s', self::ACCESS_KEY), $capturedUrl);

        $this->assertInstanceOf(CombatLogSegmentsResponse::class, $result);
        $this->assertSame(1348248, $result->sourceUserId);
        $this->assertCount(2, $result->segments);
        $this->assertSame(1, $result->segments[0]->id);
        $this->assertSame('trash', $result->segments[0]->type);
        $this->assertSame('https://s3/01_trash.txt.gz', $result->segments[0]->downloadUrl);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRun_givenInvalidResponse_returnsNull(): void
    {
        // Arrange
        $this->log->expects($this->once())->method('getCombatLogSegmentsForRunInvalidResponse');
        $service = $this->makeService(fn(): string => 'not json');

        // Act
        $result = $service->getCombatLogSegmentsForRun($this->makeSeason(), self::RUN_ID);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @param  callable(string): string $curlCallback
     * @throws Exception
     */
    private function makeService(callable $curlCallback): RaiderIOApiService&MockObject
    {
        /** @var MockObject&CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = $this->createMockPublic(CoordinatesServiceInterface::class);
        /** @var MockObject&SeasonServiceInterface $seasonService */
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        /** @var MockObject&SeasonAffixGroupServiceInterface $seasonAffixGroupService */
        $seasonAffixGroupService = $this->createMockPublic(SeasonAffixGroupServiceInterface::class);

        $service = $this->getMockBuilder(RaiderIOApiService::class)
            ->setConstructorArgs([$coordinatesService, $seasonService, $seasonAffixGroupService, $this->log])
            ->onlyMethods(['curlGet'])
            ->getMock();

        $service->method('curlGet')->willReturnCallback($curlCallback);

        return $service;
    }

    private function makeSeason(): Season
    {
        $expansion            = new Expansion();
        $expansion->shortname = Expansion::EXPANSION_MIDNIGHT;

        $season        = new Season();
        $season->index = 1;
        $season->setRelation('expansion', $expansion);

        return $season;
    }
}
