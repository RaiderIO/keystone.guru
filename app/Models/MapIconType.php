<?php

namespace App\Models;

use Eloquent;

/**
 * @property int $id
 * @property string $name
 * @property string $key
 * @property int $width
 * @property int $height
 * @property boolean $admin_only
 *
 * @property MapIcon $mapicons
 *
 * @mixin Eloquent
 */
class MapIconType extends CacheModel
{
    public const MAP_ICON_TYPE_UNKNOWN                   = 'unknown';
    public const MAP_ICON_TYPE_COMMENT                   = 'comment';
    public const MAP_ICON_TYPE_DOOR                      = 'door';
    public const MAP_ICON_TYPE_DOOR_DOWN                 = 'door_down';
    public const MAP_ICON_TYPE_DOOR_LEFT                 = 'door_left';
    public const MAP_ICON_TYPE_DOOR_LOCKED               = 'door_locked';
    public const MAP_ICON_TYPE_DOOR_RIGHT                = 'door_right';
    public const MAP_ICON_TYPE_DOOR_UP                   = 'door_up';
    public const MAP_ICON_TYPE_DOT_YELLOW                = 'dot_yellow';
    public const MAP_ICON_TYPE_DUNGEON_START             = 'dungeon_start';
    public const MAP_ICON_TYPE_GATEWAY                   = 'gateway';
    public const MAP_ICON_TYPE_GRAVEYARD                 = 'graveyard';
    public const MAP_ICON_TYPE_GREASEBOT                 = 'greasebot';
    public const MAP_ICON_TYPE_SHOCKBOT                  = 'shockbot';
    public const MAP_ICON_TYPE_WARLOCK_GATEWAY           = 'warlock_gateway';
    public const MAP_ICON_TYPE_WELDINGBOT                = 'weldingbot';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL   = 'awakened_obelisk_brutal';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED   = 'awakened_obelisk_cursed';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED  = 'awakened_obelisk_defiled';
    public const MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC = 'awakened_obelisk_entropic';

    public const MAP_ICON_TYPE_SKIP_FLIGHT   = 'skip_flight';
    public const MAP_ICON_TYPE_SKIP_TELEPORT = 'skip_teleport';
    public const MAP_ICON_TYPE_SKIP_WALK     = 'skip_walk';

    public const MAP_ICON_TYPE_RAID_MARKER_STAR     = 'raid_marker_star';
    public const MAP_ICON_TYPE_RAID_MARKER_CIRCLE   = 'raid_marker_circle';
    public const MAP_ICON_TYPE_RAID_MARKER_DIAMOND  = 'raid_marker_diamond';
    public const MAP_ICON_TYPE_RAID_MARKER_TRIANGLE = 'raid_marker_triangle';
    public const MAP_ICON_TYPE_RAID_MARKER_MOON     = 'raid_marker_moon';
    public const MAP_ICON_TYPE_RAID_MARKER_SQUARE   = 'raid_marker_square';
    public const MAP_ICON_TYPE_RAID_MARKER_CROSS    = 'raid_marker_cross';
    public const MAP_ICON_TYPE_RAID_MARKER_SKULL    = 'raid_marker_skull';

    public const MAP_ICON_TYPE_SPELL_BLOODLUST             = 'spell_bloodlust';
    public const MAP_ICON_TYPE_SPELL_HEROISM               = 'spell_heroism';
    public const MAP_ICON_TYPE_SPELL_SHADOWMELD            = 'spell_shadowmeld';
    public const MAP_ICON_TYPE_SPELL_SHROUD_OF_CONCEALMENT = 'spell_shroud_of_concealment';

    public const MAP_ICON_TYPE_ITEM_INVISIBILITY = 'item_invisibility';

    public const MAP_ICON_TYPE_QUESTION_YELLOW = 'question_yellow';
    public const MAP_ICON_TYPE_QUESTION_BLUE   = 'question_blue';
    public const MAP_ICON_TYPE_QUESTION_ORANGE = 'question_orange';

    public const MAP_ICON_TYPE_EXCLAMATION_YELLOW = 'exclamation_yellow';
    public const MAP_ICON_TYPE_EXCLAMATION_BLUE   = 'exclamation_blue';
    public const MAP_ICON_TYPE_EXCLAMATION_ORANGE = 'exclamation_orange';

    public const MAP_ICON_TYPE_NEONBUTTON_BLUE      = 'neonbutton_blue';
    public const MAP_ICON_TYPE_NEONBUTTON_CYAN      = 'neonbutton_cyan';
    public const MAP_ICON_TYPE_NEONBUTTON_GREEN     = 'neonbutton_green';
    public const MAP_ICON_TYPE_NEONBUTTON_ORANGE    = 'neonbutton_orange';
    public const MAP_ICON_TYPE_NEONBUTTON_PINK      = 'neonbutton_pink';
    public const MAP_ICON_TYPE_NEONBUTTON_PURPLE    = 'neonbutton_purple';
    public const MAP_ICON_TYPE_NEONBUTTON_RED       = 'neonbutton_red';
    public const MAP_ICON_TYPE_NEONBUTTON_YELLOW    = 'neonbutton_yellow';
    public const MAP_ICON_TYPE_NEONBUTTON_DARKRED   = 'neonbutton_darkred';
    public const MAP_ICON_TYPE_NEONBUTTON_DARKGREEN = 'neonbutton_darkgreen';
    public const MAP_ICON_TYPE_NEONBUTTON_DARKBLUE  = 'neonbutton_darkblue';

    public const MAP_ICON_TYPE_SPELL_MIND_SOOTHE = 'spell_mind_soothe';
    public const MAP_ICON_TYPE_SPELL_COMBUSTION  = 'spell_combustion';

    public const MAP_ICON_TYPE_COVENANT_KYRIAN     = 'covenant_kyrian';
    public const MAP_ICON_TYPE_COVENANT_NECROLORDS = 'covenant_necrolords';
    public const MAP_ICON_TYPE_COVENANT_NIGHTFAE   = 'covenant_nightfae';
    public const MAP_ICON_TYPE_COVENANT_VENTHYR    = 'covenant_venthyr';

    public const MAP_ICON_TYPE_PORTAL_BLUE   = 'portal_blue';
    public const MAP_ICON_TYPE_PORTAL_GREEN  = 'portal_green';
    public const MAP_ICON_TYPE_PORTAL_ORANGE = 'portal_orange';
    public const MAP_ICON_TYPE_PORTAL_PINK   = 'portal_pink';
    public const MAP_ICON_TYPE_PORTAL_RED    = 'portal_red';

    public const MAP_ICON_TYPE_NW_ITEM_ANIMA   = 'nw_item_anima';
    public const MAP_ICON_TYPE_NW_ITEM_GOLIATH = 'nw_item_goliath';
    public const MAP_ICON_TYPE_NW_ITEM_HAMMER  = 'nw_item_hammer';
    public const MAP_ICON_TYPE_NW_ITEM_SHIELD  = 'nw_item_shield';
    public const MAP_ICON_TYPE_NW_ITEM_SPEAR   = 'nw_item_spear';

    public const MAP_ICON_TYPE_SPELL_INCARNATION = 'spell_incarnation';

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
    ];

    public $timestamps = false;

    protected $fillable = [
        'name',
        'key',
        'width',
        'height',
        'admin_only',
    ];

    public function mapicons()
    {
        return $this->hasMany('App\Models\MapIcon');
    }

    public static function boot()
    {
        parent::boot();

        // This model may NOT be deleted, it's read only!
        static::deleting(function ($someModel) {
            return false;
        });
    }
}
