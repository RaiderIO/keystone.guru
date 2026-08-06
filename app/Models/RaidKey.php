<?php

namespace App\Models;

/**
 * The `key` column of every raid known to the application. Previously `Dungeon::RAID_*`.
 */
enum RaidKey: string
{
    // @formatter:off
    // Classic
    case GNOMEREGAN_SOD          = 'gnomeregan_sod';             //gnomeregan
    case ZUL_GURUB               = 'zulgurub';
    case ONYXIAS_LAIR            = 'onyxias_lair_classic';
    case MOLTEN_CORE             = 'moltencore';
    case BLACKWING_LAIR          = 'blackwinglair';
    case RUINS_OF_AHN_QIRAJ      = 'ruins_of_ahnqiraj_classic';  // 20-man (classic)
    case TEMPLE_OF_AHN_QIRAJ     = 'temple_of_ahnqiraj_classic'; // 40-man (classic)
    case RUINS_OF_AHN_QIRAJ_SOD  = 'ruins_of_ahnqiraj_sod';      // 20-man (classic)
    case TEMPLE_OF_AHN_QIRAJ_SOD = 'temple_of_ahnqiraj_sod';     // 40-man (classic)
    case NAXXRAMAS               = 'naxxramas_classic';
    case SCARLET_ENCLAVE         = 'scarlet_enclave';

    // The Burning Crusade
    case KARAZHAN                   = 'karazhan';
    case GRUULS_LAIR                = 'gruuls_lair';
    case SERPENTSHRINE_CAVERN       = 'serpentshrine_cavern';
    case MAGTHERIDONS_LAIR          = 'magtheridons_lair';
    case THE_EYE                    = 'the_eye';
    case THE_BATTLE_FOR_MOUNT_HYJAL = 'the_battle_for_mount_hyjal';
    case BLACK_TEMPLE               = 'black_temple';
    case SUNWELL_PLATEAU            = 'sunwell_plateau';

    // Wrath of the Lich King
    case ICECROWN_CITADEL                         = 'icecrowncitadel';
    case NAXXRAMAS_WOTLK                          = 'naxxramas';
    case ONYXIAS_LAIR_WOTLK                       = 'onyxias_lair';
    case CRUSADERS_COLISEUM_TRIAL_OF_THE_CRUSADER = 'theargentcoliseum';
    case THE_EYE_OF_ETERNITY                      = 'theeyeofeternity';
    case THE_OBSIDIAN_SANCTUM                     = 'theobsidiansanctum';
    case THE_RUBY_SANCTUM                         = 'therubysanctum';
    case ULDUAR                                   = 'ulduar';
    case VAULT_OF_ARCHAVON                        = 'vaultofarchavon';

    // cata
    case FIRELANDS   = 'firelands';
    case DRAGON_SOUL = 'dragonsoul';
    // @formatter:on

    /**
     * The expansion key (one of the {@see Expansion} EXPANSION_* constants) this raid belongs to.
     */
    public function expansionKey(): string
    {
        return match ($this) {
            self::GNOMEREGAN_SOD,
            self::ZUL_GURUB,
            self::ONYXIAS_LAIR,
            self::MOLTEN_CORE,
            self::BLACKWING_LAIR,
            self::RUINS_OF_AHN_QIRAJ,
            self::TEMPLE_OF_AHN_QIRAJ,
            self::RUINS_OF_AHN_QIRAJ_SOD,
            self::TEMPLE_OF_AHN_QIRAJ_SOD,
            self::NAXXRAMAS,
            self::SCARLET_ENCLAVE => Expansion::EXPANSION_CLASSIC,
            self::KARAZHAN,
            self::GRUULS_LAIR,
            self::SERPENTSHRINE_CAVERN,
            self::MAGTHERIDONS_LAIR,
            self::THE_EYE,
            self::THE_BATTLE_FOR_MOUNT_HYJAL,
            self::BLACK_TEMPLE,
            self::SUNWELL_PLATEAU => Expansion::EXPANSION_TBC,
            self::ICECROWN_CITADEL,
            self::NAXXRAMAS_WOTLK,
            self::ONYXIAS_LAIR_WOTLK,
            self::CRUSADERS_COLISEUM_TRIAL_OF_THE_CRUSADER,
            self::THE_EYE_OF_ETERNITY,
            self::THE_OBSIDIAN_SANCTUM,
            self::THE_RUBY_SANCTUM,
            self::ULDUAR,
            self::VAULT_OF_ARCHAVON => Expansion::EXPANSION_WOTLK,
            self::FIRELANDS,
            self::DRAGON_SOUL => Expansion::EXPANSION_CATACLYSM,
        };
    }

    /**
     * All cases of this enum, grouped by the expansion key they belong to.
     *
     * @return array<string, list<self>>
     */
    public static function casesByExpansionKey(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->expansionKey()][] = $case;
        }

        return $result;
    }
}
