<?php

namespace Tests\Unit\App\Service\Traits;

use App\Service\Traits\Dtos\CurlDownloadResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\CurlTraitConsumer;
use Tests\TestCases\PublicTestCase;

#[Group('Curl')]
final class CurlTest extends PublicTestCase
{
    private const string URL          = 'https://s3.example.com/02_boss.txt.gz?X-Amz-Signature=abc';
    private const string ERROR_BODY   = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>AccessDenied</Code></Error>';
    private const string SUCCESS_BODY = "8/2/2026 20:15:01.123-4  SPELL_CAST_SUCCESS,Player-1084-0B4087DE\n";

    /**
     * @throws Exception
     */
    #[Test]
    public function curlSaveToFile_givenSuccessfulResponse_writesBodyAndReturnsTrue(): void
    {
        // Arrange
        $filePath = $this->getTempFilePath();
        $consumer = $this->getConsumerWithResponse(self::SUCCESS_BODY, 200, CURLE_OK);

        try {
            // Act
            $result = $consumer->curlSaveToFile(self::URL, $filePath);

            // Assert
            $this->assertTrue($result);
            $this->assertSame(self::SUCCESS_BODY, file_get_contents($filePath));
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('failedResponseProvider')]
    public function curlSaveToFile_givenFailedResponse_writesNothingAndReturnsFalse(
        string|bool $body,
        int         $httpCode,
        int         $errorNumber,
    ): void {
        // Arrange
        $filePath = $this->getTempFilePath();
        $consumer = $this->getConsumerWithResponse($body, $httpCode, $errorNumber);

        try {
            // Act
            $result = $consumer->curlSaveToFile(self::URL, $filePath);

            // Assert — the error body must not end up on disk, or it gets handed to the combat log parser (#3789)
            $this->assertFalse($result);
            $this->assertFileDoesNotExist($filePath);
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * @return array<string, array{string|bool, int, int}>
     */
    public static function failedResponseProvider(): array
    {
        return [
            'expired presigned url (403)' => [self::ERROR_BODY, 403, CURLE_OK],
            'missing object (404)'        => [self::ERROR_BODY, 404, CURLE_OK],
            'storage error (500)'         => [self::ERROR_BODY, 500, CURLE_OK],
            'unfollowed redirect (302)'   => ['', 302, CURLE_OK],
            'transport failure'           => [false, 0, CURLE_COULDNT_CONNECT],
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function curlDownloadToFile_givenFailedResponse_returnsResultWithHttpCodeAndErrorNumber(): void
    {
        // Arrange
        $filePath = $this->getTempFilePath();
        $consumer = $this->getConsumerWithResponse('', 200, CURLE_BAD_CONTENT_ENCODING);

        try {
            // Act
            $result = $consumer->curlDownloadToFile(self::URL, $filePath);

            // Assert
            $this->assertFalse($result->succeeded);
            $this->assertSame(200, $result->httpCode);
            $this->assertSame(CURLE_BAD_CONTENT_ENCODING, $result->errorNumber);
            $this->assertSame('curl failed', $result->errorMessage);
            $this->assertSame(1.25, $result->durationSeconds);
            $this->assertFileDoesNotExist($filePath);
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function curlDownloadToFile_givenSuccessfulResponse_writesBodyAndReturnsSucceededResult(): void
    {
        // Arrange
        $filePath = $this->getTempFilePath();
        $consumer = $this->getConsumerWithResponse(self::SUCCESS_BODY, 200, CURLE_OK);

        try {
            // Act
            $result = $consumer->curlDownloadToFile(self::URL, $filePath);

            // Assert
            $this->assertTrue($result->succeeded);
            $this->assertFalse($result->isPermanent());
            $this->assertFalse($result->isExpiredOrDenied());
            $this->assertSame(self::SUCCESS_BODY, file_get_contents($filePath));
        } finally {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    #[Test]
    #[DataProvider('downloadFailureClassificationProvider')]
    public function curlDownloadResult_givenFailure_classifiesAsPermanentOrExpired(
        int  $httpCode,
        int  $errorNumber,
        bool $expectedPermanent,
        bool $expectedExpiredOrDenied,
    ): void {
        // Arrange
        $result = new CurlDownloadResult(
            succeeded:       false,
            httpCode:        $httpCode,
            errorNumber:     $errorNumber,
            errorMessage:    '',
            durationSeconds: 0.0,
        );

        // Act
        $isPermanent       = $result->isPermanent();
        $isExpiredOrDenied = $result->isExpiredOrDenied();

        // Assert
        $this->assertSame($expectedPermanent, $isPermanent);
        $this->assertSame($expectedExpiredOrDenied, $isExpiredOrDenied);
    }

    /**
     * @return array<string, array{int, int, bool, bool}>
     */
    public static function downloadFailureClassificationProvider(): array
    {
        return [
            'bad content encoding'      => [200, CURLE_BAD_CONTENT_ENCODING, true, false],
            'missing object (404)'      => [404, CURLE_OK, true, false],
            'bad request (400)'         => [400, CURLE_OK, true, false],
            'expired or denied (403)'   => [403, CURLE_OK, false, true],
            'request timeout (408)'     => [408, CURLE_OK, false, false],
            'too many requests (429)'   => [429, CURLE_OK, false, false],
            'storage error (500)'       => [500, CURLE_OK, false, false],
            'service unavailable (503)' => [503, CURLE_OK, false, false],
            'unfollowed redirect (302)' => [302, CURLE_OK, false, false],
            'timed out'                 => [0, CURLE_OPERATION_TIMEDOUT, false, true],
            'could not connect'         => [0, CURLE_COULDNT_CONNECT, false, false],
            'connection reset'          => [200, CURLE_RECV_ERROR, false, false],
        ];
    }

    private function getTempFilePath(): string
    {
        return sprintf('%s/curl_test_%s.txt', sys_get_temp_dir(), uniqid());
    }

    /**
     * Build a trait consumer with its single network-touching method stubbed out.
     *
     * @throws Exception
     * @return CurlTraitConsumer&MockObject
     */
    private function getConsumerWithResponse(string|bool $body, int $httpCode, int $errorNumber): MockObject
    {
        $consumer = $this->getMockBuilder(CurlTraitConsumer::class)
            ->onlyMethods(['curlGetResponse'])
            ->getMock();

        $consumer->method('curlGetResponse')
            ->willReturn([
                'body'            => $body,
                'httpCode'        => $httpCode,
                'errorNumber'     => $errorNumber,
                'errorMessage'    => $errorNumber === CURLE_OK ? '' : 'curl failed',
                'durationSeconds' => 1.25,
            ]);

        return $consumer;
    }
}
