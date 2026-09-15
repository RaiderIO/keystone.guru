<?php

namespace App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Service\Wowhead\Dtos\SpellDataResult;

interface WowheadServiceInterface
{
    public function getNpcHealth(GameVersion $gameVersion, Npc $npc): ?int;

    public function downloadMissingSpellIcons(): bool;

    /**
     * Downloads an icon off Wowhead's CDN by its icon file name (without extension) into $targetFolder.
     */
    public function downloadIcon(string $iconName, string $targetFolder): bool;

    public function getNpcDisplayId(GameVersion $gameVersion, Npc $npc, ?string $html = null): ?int;

    public function getSpellData(GameVersion $gameVersion, int $spellId, ?string $html = null): ?SpellDataResult;

    /**
     * The plain text of Wowhead's rendered tooltip for a spell - the name, its range and cast time, and
     * the description with its numbers already worked out.
     */
    public function getSpellTooltipText(int $spellId): ?string;
}
