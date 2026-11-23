<?php

namespace Tests\Unit\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteAiSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DungeonRouteAiSource')]
final class DungeonRouteAiSourceTest extends TestCase
{
    #[Test]
    #[DataProvider('clientNameProvider')]
    public function fromClientName_givenClientName_returnsExpectedSource(?string $clientName, DungeonRouteAiSource $expected): void
    {
        // Act
        $result = DungeonRouteAiSource::fromClientName($clientName);

        // Assert
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{0: string|null, 1: DungeonRouteAiSource}> */
    public static function clientNameProvider(): array
    {
        return [
            'claude code'   => ['Claude Code (ksg-local)', DungeonRouteAiSource::Claude],
            'claude.ai'     => ['claude.ai', DungeonRouteAiSource::Claude],
            'codex'         => ['Codex', DungeonRouteAiSource::Codex],
            'codex in name' => ['OpenAI codex-cli', DungeonRouteAiSource::Codex],
            'other client'  => ['Cursor', DungeonRouteAiSource::Other],
            'no client'     => [null, DungeonRouteAiSource::Other],
        ];
    }
}
