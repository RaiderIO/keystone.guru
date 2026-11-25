<?php

namespace App\Models\DungeonRoute;

/**
 * The kind of AI client that created a route through the MCP server, as stored in `dungeon_routes`.`ai_source`.
 */
enum DungeonRouteAiSource: string
{
    case Claude = 'claude';
    case Codex  = 'codex';
    case Other  = 'other';

    /**
     * Classifies the name an OAuth client registered itself with (e.g. "Claude Code", "claude.ai", "Codex"). The name
     * is self-declared by the client, so this is a best guess, never a security decision.
     */
    public static function fromClientName(?string $clientName): self
    {
        $clientName = strtolower($clientName ?? '');

        return match (true) {
            str_contains($clientName, 'claude') => self::Claude,
            str_contains($clientName, 'codex')  => self::Codex,
            default                             => self::Other,
        };
    }
}
