<?php

namespace App\Models;

use App\Models\Traits\SeederModel;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int     $id
 * @property string  $name
 * @property string  $key
 * @property int     $width
 * @property int     $height
 * @property bool    $admin_only
 * @property MapIcon $mapIcons
 *
 * @mixin Eloquent
 */
class MapIconType extends CacheModel
{
    use SeederModel;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'key',
        'width',
        'height',
        'admin_only',
    ];

    public const MAP_ICON_TYPE_UNKNOWN                           = 'unknown';
    public const MAP_ICON_TYPE_COMMENT                           = 'comment';
    public const MAP_ICON_TYPE_DOOR                              = 'door';
    public const MAP_ICON_TYPE_DOOR_DOWN                         = 'door_down';
    public const MAP_ICON_TYPE_DOOR_LEFT                         = 'door_left';
    public const MAP_ICON_TYPE_DOOR_LOCKED                       = 'door_locked';
    public const MAP_ICON_TYPE_DOOR_RIGHT                        = 'door_right';
    public const MAP_ICON_TYPE_DOOR_UP                           = 'door_up';
    public const MAP_ICON_TYPE_DOT_YELLOW                        = 'dot_yellow';
    public const MAP_ICON_TYPE_DUNGEON_START                     = 'dungeon_start';
    public const MAP_ICON_TYPE_GATEWAY                           = 'gateway';
    public const MAP_ICON_TYPE_GRAVEYARD                         = 'graveyard';
    public const MAP_ICON_TYPE_GREASEBOT                         = 'greasebot';
    public const MAP_ICON_TYPE_SHOCKBOT                          = 'shockbot';
    public const MAP_ICON_TYPE_WARLOCK_GATEWAY                   = 'warlock_gateway';
    public const MAP_ICON_TYPE_WELDINGBOT                        = 'weldingbot';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL           = 'awakened_obelisk_brutal';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED           = 'awakened_obelisk_cursed';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED          = 'awakened_obelisk_defiled';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC         = 'awakened_obelisk_entropic';
    public const MAP_ICON_TYPE_SKIP_FLIGHT                       = 'skip_flight';
    public const MAP_ICON_TYPE_SKIP_TELEPORT                     = 'skip_teleport';
    public const MAP_ICON_TYPE_SKIP_WALK                         = 'skip_walk';
    public const MAP_ICON_TYPE_RAID_MARKER_STAR                  = 'raid_marker_star';
    public const MAP_ICON_TYPE_RAID_MARKER_CIRCLE                = 'raid_marker_circle';
    public const MAP_ICON_TYPE_RAID_MARKER_DIAMOND               = 'raid_marker_diamond';
    public const MAP_ICON_TYPE_RAID_MARKER_TRIANGLE              = 'raid_marker_triangle';
    public const MAP_ICON_TYPE_RAID_MARKER_MOON                  = 'raid_marker_moon';
    public const MAP_ICON_TYPE_RAID_MARKER_SQUARE                = 'raid_marker_square';
    public const MAP_ICON_TYPE_RAID_MARKER_CROSS                 = 'raid_marker_cross';
    public const MAP_ICON_TYPE_RAID_MARKER_SKULL                 = 'raid_marker_skull';
    public const MAP_ICON_TYPE_SPELL_BLOODLUST                   = 'spell_bloodlust';
    public const MAP_ICON_TYPE_SPELL_HEROISM                     = 'spell_heroism';
    public const MAP_ICON_TYPE_SPELL_SHADOWMELD                  = 'spell_shadowmeld';
    public const MAP_ICON_TYPE_SPELL_SHROUD_OF_CONCEALMENT       = 'spell_shroud_of_concealment';
    public const MAP_ICON_TYPE_ITEM_INVISIBILITY                 = 'item_invisibility';
    public const MAP_ICON_TYPE_ITEM_DRUMS_OF_SPEED               = 'item_drums_of_speed';
    public const MAP_ICON_TYPE_ITEM_FREE_ACTION_POTION           = 'item_free_action_potion';
    public const MAP_ICON_TYPE_ITEM_GLOBAL_THERMAL_SAPPER_CHARGE = 'item_global_thermal_sapper_charge';
    public const MAP_ICON_TYPE_ITEM_ROCKET_BOOTS_XTREME          = 'item_rocket_boots_xtreme';
    public const MAP_ICON_TYPE_QUESTION_YELLOW                   = 'question_yellow';
    public const MAP_ICON_TYPE_QUESTION_BLUE                     = 'question_blue';
    public const MAP_ICON_TYPE_QUESTION_ORANGE                   = 'question_orange';
    public const MAP_ICON_TYPE_EXCLAMATION_YELLOW                = 'exclamation_yellow';
    public const MAP_ICON_TYPE_EXCLAMATION_BLUE                  = 'exclamation_blue';
    public const MAP_ICON_TYPE_EXCLAMATION_ORANGE                = 'exclamation_orange';
    public const MAP_ICON_TYPE_NEONBUTTON_BLUE                   = 'neonbutton_blue';
    public const MAP_ICON_TYPE_NEONBUTTON_CYAN                   = 'neonbutton_cyan';
    public const MAP_ICON_TYPE_NEONBUTTON_GREEN                  = 'neonbutton_green';
    public const MAP_ICON_TYPE_NEONBUTTON_ORANGE                 = 'neonbutton_orange';
    public const MAP_ICON_TYPE_NEONBUTTON_PINK                   = 'neonbutton_pink';
    public const MAP_ICON_TYPE_NEONBUTTON_PURPLE                 = 'neonbutton_purple';
    public const MAP_ICON_TYPE_NEONBUTTON_RED                    = 'neonbutton_red';
    public const MAP_ICON_TYPE_NEONBUTTON_YELLOW                 = 'neonbutton_yellow';
    public const MAP_ICON_TYPE_NEONBUTTON_DARKRED                = 'neonbutton_darkred';
    public const MAP_ICON_TYPE_NEONBUTTON_DARKGREEN              = 'neonbutton_darkgreen';
    public const MAP_ICON_TYPE_NEONBUTTON_DARKBLUE               = 'neonbutton_darkblue';
    public const MAP_ICON_TYPE_SPELL_MIND_SOOTHE                 = 'spell_mind_soothe';
    public const MAP_ICON_TYPE_SPELL_COMBUSTION                  = 'spell_combustion';
    public const MAP_ICON_TYPE_COVENANT_KYRIAN                   = 'covenant_kyrian';
    public const MAP_ICON_TYPE_COVENANT_NECROLORDS               = 'covenant_necrolords';
    public const MAP_ICON_TYPE_COVENANT_NIGHTFAE                 = 'covenant_nightfae';
    public const MAP_ICON_TYPE_COVENANT_VENTHYR                  = 'covenant_venthyr';
    public const MAP_ICON_TYPE_PORTAL_BLUE                       = 'portal_blue';
    public const MAP_ICON_TYPE_PORTAL_GREEN                      = 'portal_green';
    public const MAP_ICON_TYPE_PORTAL_ORANGE                     = 'portal_orange';
    public const MAP_ICON_TYPE_PORTAL_PINK                       = 'portal_pink';
    public const MAP_ICON_TYPE_PORTAL_RED                        = 'portal_red';
    public const MAP_ICON_TYPE_NW_ITEM_ANIMA                     = 'nw_item_anima';
    public const MAP_ICON_TYPE_NW_ITEM_GOLIATH                   = 'nw_item_goliath';
    public const MAP_ICON_TYPE_NW_ITEM_HAMMER                    = 'nw_item_hammer';
    public const MAP_ICON_TYPE_NW_ITEM_SHIELD                    = 'nw_item_shield';
    public const MAP_ICON_TYPE_NW_ITEM_SPEAR                     = 'nw_item_spear';
    public const MAP_ICON_TYPE_SPELL_INCARNATION                 = 'spell_incarnation';
    public const MAP_ICON_TYPE_SPELL_MISDIRECTION                = 'spell_misdirection';
    public const MAP_ICON_TYPE_SPELL_TRICKS_OF_THE_TRADE         = 'spell_tricks_of_the_trade';
    public const MAP_ICON_TYPE_ROLE_TANK                         = 'role_tank';
    public const MAP_ICON_TYPE_ROLE_HEALER                       = 'role_healer';
    public const MAP_ICON_TYPE_ROLE_DPS                          = 'role_dps';
    public const MAP_ICON_TYPE_CLASS_WARRIOR                     = 'class_warrior';
    public const MAP_ICON_TYPE_CLASS_HUNTER                      = 'class_hunter';
    public const MAP_ICON_TYPE_CLASS_DEATH_KNIGHT                = 'class_deathknight';
    public const MAP_ICON_TYPE_CLASS_MAGE                        = 'class_mage';
    public const MAP_ICON_TYPE_CLASS_PRIEST                      = 'class_priest';
    public const MAP_ICON_TYPE_CLASS_MONK                        = 'class_monk';
    public const MAP_ICON_TYPE_CLASS_ROGUE                       = 'class_rogue';
    public const MAP_ICON_TYPE_CLASS_WARLOCK                     = 'class_warlock';
    public const MAP_ICON_TYPE_CLASS_SHAMAN                      = 'class_shaman';
    public const MAP_ICON_TYPE_CLASS_PALADIN                     = 'class_paladin';
    public const MAP_ICON_TYPE_CLASS_DRUID                       = 'class_druid';
    public const MAP_ICON_TYPE_CLASS_DEMON_HUNTER                = 'class_demonhunter';
    public const MAP_ICON_TYPE_CLASS_EVOKER                      = 'class_evoker';
    public const MAP_ICON_TYPE_CHEST                             = 'chest';
    public const MAP_ICON_TYPE_CHEST_LOCKED                      = 'chest_locked';
    public const MAP_ICON_TYPE_MISTS_STATSHROOM                  = 'mists_item_statshroom';
    public const MAP_ICON_TYPE_MISTS_TOUGHSHROOM                 = 'mists_item_toughshroom';
    public const MAP_ICON_TYPE_MISTS_OVERGROWN_ROOTS             = 'mists_item_overgrown_roots';
    public const MAP_ICON_TYPE_COT_SHADECASTER                   = 'cot_item_shadecaster';
    public const MAP_ICON_TYPE_SV_IMBUED_IRON_ENERGY             = 'sv_item_imbued_iron_energy';
    public const MAP_ICON_TYPE_ARA_KARA_SILK_WRAP                = 'ara_kara_item_silk_wrap';

    public const ALL = [
        self::MAP_ICON_TYPE_UNKNOWN                   => 1,
        self::MAP_ICON_TYPE_COMMENT                   => 2,
        self::MAP_ICON_TYPE_DOOR                      => 3,
        self::MAP_ICON_TYPE_DOOR_DOWN                 => 4,
        self::MAP_ICON_TYPE_DOOR_LEFT                 => 5,
        self::MAP_ICON_TYPE_DOOR_LOCKED               => 6,
        self::MAP_ICON_TYPE_DOOR_RIGHT                => 7,
        self::MAP_ICON_TYPE_DOOR_UP                   => 8,
        self::MAP_ICON_TYPE_DOT_YELLOW                => 9,
        self::MAP_ICON_TYPE_DUNGEON_START             => 10,
        self::MAP_ICON_TYPE_GATEWAY                   => 11,
        self::MAP_ICON_TYPE_GRAVEYARD                 => 12,
        self::MAP_ICON_TYPE_GREASEBOT                 => 13,
        self::MAP_ICON_TYPE_SHOCKBOT                  => 14,
        self::MAP_ICON_TYPE_WARLOCK_GATEWAY           => 15,
        self::MAP_ICON_TYPE_WELDINGBOT                => 16,
        self::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL   => 17,
        self::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED   => 18,
        self::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED  => 19,
        self::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC => 20,

        self::MAP_ICON_TYPE_SKIP_FLIGHT   => 21,
        self::MAP_ICON_TYPE_SKIP_TELEPORT => 22,
        self::MAP_ICON_TYPE_SKIP_WALK     => 23,

        self::MAP_ICON_TYPE_RAID_MARKER_STAR     => 24,
        self::MAP_ICON_TYPE_RAID_MARKER_CIRCLE   => 25,
        self::MAP_ICON_TYPE_RAID_MARKER_DIAMOND  => 26,
        self::MAP_ICON_TYPE_RAID_MARKER_TRIANGLE => 27,
        self::MAP_ICON_TYPE_RAID_MARKER_MOON     => 28,
        self::MAP_ICON_TYPE_RAID_MARKER_SQUARE   => 29,
        self::MAP_ICON_TYPE_RAID_MARKER_CROSS    => 30,
        self::MAP_ICON_TYPE_RAID_MARKER_SKULL    => 31,

        self::MAP_ICON_TYPE_SPELL_BLOODLUST             => 32,
        self::MAP_ICON_TYPE_SPELL_HEROISM               => 33,
        self::MAP_ICON_TYPE_SPELL_SHADOWMELD            => 34,
        self::MAP_ICON_TYPE_SPELL_SHROUD_OF_CONCEALMENT => 35,

        self::MAP_ICON_TYPE_ITEM_INVISIBILITY => 36,

        self::MAP_ICON_TYPE_QUESTION_YELLOW => 37,
        self::MAP_ICON_TYPE_QUESTION_BLUE   => 38,
        self::MAP_ICON_TYPE_QUESTION_ORANGE => 39,

        self::MAP_ICON_TYPE_EXCLAMATION_YELLOW => 40,
        self::MAP_ICON_TYPE_EXCLAMATION_BLUE   => 41,
        self::MAP_ICON_TYPE_EXCLAMATION_ORANGE => 42,

        self::MAP_ICON_TYPE_NEONBUTTON_BLUE      => 43,
        self::MAP_ICON_TYPE_NEONBUTTON_CYAN      => 44,
        self::MAP_ICON_TYPE_NEONBUTTON_GREEN     => 45,
        self::MAP_ICON_TYPE_NEONBUTTON_ORANGE    => 46,
        self::MAP_ICON_TYPE_NEONBUTTON_PINK      => 47,
        self::MAP_ICON_TYPE_NEONBUTTON_PURPLE    => 48,
        self::MAP_ICON_TYPE_NEONBUTTON_RED       => 49,
        self::MAP_ICON_TYPE_NEONBUTTON_YELLOW    => 50,
        self::MAP_ICON_TYPE_NEONBUTTON_DARKRED   => 51,
        self::MAP_ICON_TYPE_NEONBUTTON_DARKGREEN => 52,
        self::MAP_ICON_TYPE_NEONBUTTON_DARKBLUE  => 53,

        self::MAP_ICON_TYPE_SPELL_MIND_SOOTHE => 54,
        self::MAP_ICON_TYPE_SPELL_COMBUSTION  => 55,

        self::MAP_ICON_TYPE_COVENANT_KYRIAN     => 56,
        self::MAP_ICON_TYPE_COVENANT_NECROLORDS => 57,
        self::MAP_ICON_TYPE_COVENANT_NIGHTFAE   => 58,
        self::MAP_ICON_TYPE_COVENANT_VENTHYR    => 59,

        self::MAP_ICON_TYPE_PORTAL_BLUE   => 60,
        self::MAP_ICON_TYPE_PORTAL_GREEN  => 61,
        self::MAP_ICON_TYPE_PORTAL_ORANGE => 62,
        self::MAP_ICON_TYPE_PORTAL_PINK   => 63,
        self::MAP_ICON_TYPE_PORTAL_RED    => 64,

        self::MAP_ICON_TYPE_NW_ITEM_ANIMA   => 65,
        self::MAP_ICON_TYPE_NW_ITEM_GOLIATH => 66,
        self::MAP_ICON_TYPE_NW_ITEM_HAMMER  => 67,
        self::MAP_ICON_TYPE_NW_ITEM_SHIELD  => 68,
        self::MAP_ICON_TYPE_NW_ITEM_SPEAR   => 69,

        self::MAP_ICON_TYPE_SPELL_INCARNATION => 70,

        self::MAP_ICON_TYPE_ITEM_DRUMS_OF_SPEED               => 71,
        self::MAP_ICON_TYPE_ITEM_FREE_ACTION_POTION           => 72,
        self::MAP_ICON_TYPE_ITEM_GLOBAL_THERMAL_SAPPER_CHARGE => 73,
        self::MAP_ICON_TYPE_ITEM_ROCKET_BOOTS_XTREME          => 74,

        self::MAP_ICON_TYPE_SPELL_MISDIRECTION        => 75,
        self::MAP_ICON_TYPE_SPELL_TRICKS_OF_THE_TRADE => 76,

        self::MAP_ICON_TYPE_ROLE_TANK   => 77,
        self::MAP_ICON_TYPE_ROLE_HEALER => 78,
        self::MAP_ICON_TYPE_ROLE_DPS    => 79,

        self::MAP_ICON_TYPE_CLASS_WARRIOR      => 80,
        self::MAP_ICON_TYPE_CLASS_HUNTER       => 81,
        self::MAP_ICON_TYPE_CLASS_DEATH_KNIGHT => 82,
        self::MAP_ICON_TYPE_CLASS_MAGE         => 83,
        self::MAP_ICON_TYPE_CLASS_PRIEST       => 84,
        self::MAP_ICON_TYPE_CLASS_MONK         => 85,
        self::MAP_ICON_TYPE_CLASS_ROGUE        => 86,
        self::MAP_ICON_TYPE_CLASS_WARLOCK      => 87,
        self::MAP_ICON_TYPE_CLASS_SHAMAN       => 88,
        self::MAP_ICON_TYPE_CLASS_PALADIN      => 89,
        self::MAP_ICON_TYPE_CLASS_DRUID        => 90,
        self::MAP_ICON_TYPE_CLASS_DEMON_HUNTER => 91,
        self::MAP_ICON_TYPE_CLASS_EVOKER       => 92,

        self::MAP_ICON_TYPE_CHEST        => 93,
        self::MAP_ICON_TYPE_CHEST_LOCKED => 94,

        self::MAP_ICON_TYPE_MISTS_STATSHROOM  => 95,
        self::MAP_ICON_TYPE_MISTS_TOUGHSHROOM => 96,
        self::MAP_ICON_TYPE_MISTS_OVERGROWN_ROOTS => 100,

        self::MAP_ICON_TYPE_COT_SHADECASTER => 97,

        self::MAP_ICON_TYPE_SV_IMBUED_IRON_ENERGY => 98,

        self::MAP_ICON_TYPE_ARA_KARA_SILK_WRAP => 99,
        // 101 next
    ];

    public function mapIcons(): HasMany
    {
        return $this->hasMany(MapIcon::class);
    }
}
