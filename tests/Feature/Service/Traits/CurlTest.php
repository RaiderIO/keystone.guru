<?php

namespace Tests\Feature\Service\Traits;

use App\Service\Traits\Curl;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Exercises the real curl transport (via a local PHP built-in server) rather than mocking it, since the
 * bug this covers (#3789) is specifically about curlSaveToFile() failing to notice a non-2xx response.
 */
#[Group('Service')]
final class CurlTest extends PublicTestCase
{
    private static int $port;

    /** @var resource|null */
    private static $serverProcess = null;

    private static string $routerPath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$port       = self::findFreePort();
        self::$routerPath = sprintf('%s/curl_test_router_%s.php', sys_get_temp_dir(), uniqid());

        file_put_contents(
            self::$routerPath,
            <<<'PHP'
<?php
$status = (int)($_GET['status'] ?? 200);
http_response_code($status);
echo $status >= 200 && $status < 300 ? 'OK-BODY' : '<?xml version="1.0" encoding="UTF-8"?><Error/>';
PHP,
        );

        self::$serverProcess = proc_open(
            sprintf('exec php -S 127.0.0.1:%d %s', self::$port, escapeshellarg(self::$routerPath)),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.1);

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(50000);
        }

        self::fail('Local test HTTP server did not start in time.');
    }

    /**
     * Binding to port 0 asks the OS to hand back an ephemeral free port, avoiding collisions with
     * whatever else happens to be listening (a fixed/random port range is a CI flake waiting to happen).
     */
    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if ($socket === false) {
            self::fail(sprintf('Could not allocate a free port: %s', $errstr));
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int)substr((string)strrchr($name, ':'), 1);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$serverProcess !== null) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
        }

        if (file_exists(self::$routerPath)) {
            unlink(self::$routerPath);
        }

        parent::tearDownAfterClass();
    }

    private function url(int $status): string
    {
        return sprintf('http://127.0.0.1:%d/?status=%d', self::$port, $status);
    }

    private function subject(): object
    {
        return new class {
            use Curl;
        };
    }

    #[Test]
    public function curlSaveToFile_given200Response_writesBodyAndReturnsTrue(): void
    {
        // Arrange
        $tempPath = tempnam(sys_get_temp_dir(), 'curl_test_');

        try {
            // Act
            $result = $this->subject()->curlSaveToFile($this->url(200), $tempPath);

            // Assert
            $this->assertTrue($result);
            $this->assertSame('OK-BODY', file_get_contents($tempPath));
        } finally {
            @unlink($tempPath);
        }
    }

    #[Test]
    public function curlSaveToFile_given500Response_doesNotWriteFileAndReturnsFalse(): void
    {
        // Arrange
        $tempPath = tempnam(sys_get_temp_dir(), 'curl_test_');
        unlink($tempPath);

        // Act
        $result = $this->subject()->curlSaveToFile($this->url(500), $tempPath);

        // Assert
        $this->assertFalse($result);
        $this->assertFileDoesNotExist($tempPath);
    }

    #[Test]
    public function curlSaveToFile_given403Response_doesNotWriteFileAndReturnsFalse(): void
    {
        // Arrange — mirrors an expired/invalid presigned S3 URL, the real-world trigger for #3789.
        $tempPath = tempnam(sys_get_temp_dir(), 'curl_test_');
        unlink($tempPath);

        // Act
        $result = $this->subject()->curlSaveToFile($this->url(403), $tempPath);

        // Assert
        $this->assertFalse($result);
        $this->assertFileDoesNotExist($tempPath);
    }

    #[Test]
    public function curlSaveToFile_givenConnectionFailure_returnsFalseWithoutWritingFile(): void
    {
        // Arrange
        $tempPath = tempnam(sys_get_temp_dir(), 'curl_test_');
        unlink($tempPath);

        // Act — nothing listens on this port, so curl fails at the transport level.
        $result = $this->subject()->curlSaveToFile('http://127.0.0.1:1/', $tempPath);

        // Assert
        $this->assertFalse($result);
        $this->assertFileDoesNotExist($tempPath);
    }

    #[Test]
    public function curlGet_given500Response_stillReturnsRawBody(): void
    {
        // curlGet() keeps returning whatever the server sent regardless of status, unlike
        // curlSaveToFile() — existing callers already handle/ignore malformed bodies themselves.

        // Act
        $result = $this->subject()->curlGet($this->url(500));

        // Assert
        $this->assertStringContainsString('<?xml', $result);
    }
}
