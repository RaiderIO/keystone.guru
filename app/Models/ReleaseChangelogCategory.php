<?php

namespace App\Models;

use Eloquent;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $category
 *
 * @mixin Eloquent
 */
class ReleaseChangelogCategory extends CacheModel
{
    public const GENERAL_CHANGES          = 'general_changes';
    public const ROUTE_CHANGES            = 'route_changes';
    public const MAP_CHANGES              = 'map_changes';
    public const MAPPING_CHANGES          = 'mapping_changes';
    public const BUGFIXES                 = 'bugfixes';
    public const MDT_IMPORTER_CHANGES     = 'mdt_importer_changes';
    public const TEAM_CHANGES             = 'team_changes';
    public const MDT_EXPORTER_CHANGES     = 'mdt_exporter_changes';
    public const LIVE_SESSION_CHANGES     = 'live_session_changes';
    public const SIMULATION_CRAFT_CHANGES = 'simulation_craft_changes';

    public const ALL = [
        self::GENERAL_CHANGES          => 1,
        self::ROUTE_CHANGES            => 2,
        self::MAP_CHANGES              => 3,
        self::MAPPING_CHANGES          => 4,
        self::BUGFIXES                 => 5,
        self::MDT_IMPORTER_CHANGES     => 6,
        self::TEAM_CHANGES             => 7,
        self::MDT_EXPORTER_CHANGES     => 8,
        self::LIVE_SESSION_CHANGES     => 9,
        self::SIMULATION_CRAFT_CHANGES => 10,
    ];

    public $table = 'release_changelog_categories';
    public $timestamps = false;
    protected $visible = ['id', 'key', 'name'];
    protected $fillable = ['id', 'key', 'name'];
}
