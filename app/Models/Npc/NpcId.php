<?php

namespace App\Models\Npc;

/**
 * Well-known `npc_id` values referenced by name from PHP code (dungeon builder Rules/, their tests) instead of as a
 * bare integer. Not exhaustive - only npc_ids that are actually referenced by name somewhere get a case here; add one
 * when you introduce a new reference rather than pre-populating a dungeon.
 */
enum NpcId: int
{
    // King's Rest
    case THUNDERING_TOTEM            = 135761;
    case EXPLOSIVE_TOTEM             = 135764;
    case TORRENT_TOTEM               = 135765;
    case AKAALI_THE_CONQUEROR        = 269808;
    case ZANAZAL_THE_WISE            = 269810;
    case KULA_THE_BUTCHER            = 269811;
    case MINION_OF_ZUL               = 138493;
    case MINION_OF_ZUL_EARLY_DUNGEON = 133943; // Same name, but mapped in the early dungeon packs - not the Shadow of Zul's pack
    case SHADOW_OF_ZUL               = 138489;
    case REBAN                       = 136984;
    case TZALA                       = 136976;
    case KING_DAZAR                  = 136160;

    // The Blinding Vale
    case LIGHTWARDEN_RUIA       = 245912;
    case RADIANT_SPELLSOWER     = 245336;
    case UNDERBRUSH_STALKER     = 245339;
    case LIGHTGORGED_LASHER     = 245345;
    case VIRID_GROVEKEEPER      = 245346;
    case LASHER                 = 245410;
    case THORNY_SAPTOR          = 245473;
    case LIGHTFEATHER_PETALWING = 245484;
    case SPOREBLIGHT_BELCHER    = 254850;
}
