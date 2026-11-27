<?php

namespace Tests\Fixtures;

/**
 * A stream wrapper over an in-memory disk that holds $capacityBytes bytes across all of its files, like a tmpfs
 * running out of space: a write stores what still fits and makes no progress once the disk is full, without an
 * error. Files read back what was stored, and every unlinked path is recorded.
 */
class FillingDiskStreamWrapper
{
    public const string PROTOCOL = 'fillingdisk';

    private static int $capacityBytes = 0;

    /** @var array<string, string> Contents by path */
    private static array $files = [];

    /** @var array<int, string> */
    private static array $unlinkedPaths = [];

    private string $path = '';

    private int $position = 0;

    /** @var resource|null Set by PHP for every stream it opens */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->path     = $path;
        $this->position = 0;

        if (str_starts_with($mode, 'w')) {
            self::$files[$path] = '';
        }

        return isset(self::$files[$path]);
    }

    public function stream_write(string $data): int
    {
        $freeBytes = self::$capacityBytes - array_sum(array_map(strlen(...), self::$files));
        $stored    = substr($data, 0, max(0, $freeBytes));

        self::$files[$this->path] .= $stored;

        return strlen($stored);
    }

    public function stream_read(int $count): string
    {
        $data = substr(self::$files[$this->path], $this->position, $count);
        $this->position += strlen($data);

        return $data;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$files[$this->path]);
    }

    /**
     * @return array{size: int}
     */
    public function stream_stat(): array
    {
        return ['size' => strlen(self::$files[$this->path])];
    }

    /**
     * @return array{size: int}|false
     */
    public function url_stat(string $path, int $flags): array|false
    {
        return isset(self::$files[$path]) ? ['size' => strlen(self::$files[$path])] : false;
    }

    public function unlink(string $path): bool
    {
        self::$unlinkedPaths[] = $path;
        unset(self::$files[$path]);

        return true;
    }

    public static function register(int $capacityBytes): void
    {
        self::$capacityBytes = $capacityBytes;
        self::$files         = [];
        self::$unlinkedPaths = [];

        stream_wrapper_register(self::PROTOCOL, self::class);
    }

    public static function unregister(): void
    {
        stream_wrapper_unregister(self::PROTOCOL);

        self::$files         = [];
        self::$unlinkedPaths = [];
    }

    /**
     * @return array<int, string>
     */
    public static function getUnlinkedPaths(): array
    {
        return self::$unlinkedPaths;
    }
}
