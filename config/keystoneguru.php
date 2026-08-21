<?php

return [
    // This is really only to give some admins more power than other admins - there's no point in changing this for nefarious reasons
    'super_admins' => [
        'Admin',
    ],

    // sh/worktree.sh sets COMPOSE_PROJECT_NAME to "ksg-<branch>" for a worktree stack; unset on the
    // main stack and in production, so this is null there.
    'worktree' => ($composeProjectName = env('COMPOSE_PROJECT_NAME')) !== null && str_starts_with($composeProjectName, 'ksg-')
        ? substr($composeProjectName, strlen('ksg-'))
        : null,

    'db_backup_dir'            => env('DB_BACKUP_DIR'),
    'mapping_backup_dir'       => env('MAPPING_BACKUP_DIR'),
    'assets_base_url'          => env('ASSETS_BASE_URL', '/'),
    'assets_base_url_internal' => env('ASSETS_BASE_URL_INTERNAL', env('ASSETS_BASE_URL', '/')),
    'images_base_url'          => sprintf('%s/images', env('ASSETS_BASE_URL', '')),
    'tiles_base_url'           => sprintf('%s/tiles', env('ASSETS_BASE_URL', '')),
    'tiles_base_url_internal'  => sprintf('%s/tiles', env('ASSETS_BASE_URL_INTERNAL', env('ASSETS_BASE_URL', ''))),

    'github_username'         => 'Wotuu',
    'github_repository_owner' => 'RaiderIO',
    'github_repository'       => 'Keystone.guru',

    'sanitize_text' => [
        'allowed_tags'    => ['a', 'h4', 'h5', 'h6', 'b', 'i', 'br'],
        'allowed_domains' => [
            'keystone.guru',
            'raider.io',
            'twitch.tv',
            'youtube.com',
            'wowhead.com',
            'icy-veins.com',
            'worldofwarcraft.blizzard.com',
            'wowpedia.fandom.com',
            'wowwiki-archive.fandom.com',
            'wago.io',
            'curseforge.com',
            'warcraftlogs.com',
            'archon.gg',
        ],
    ],

    'character' => [
        /** // https://wowpedia.fandom.com/wiki/Movement */
        'default_movement_speed_yards_second' => 7,
        'mounted_movement_speed_yards_second' => 14,
        'mount_cast_time_seconds'             => 1.5,
    ],

    'keystone' => [
        'timer' => [
            'plustwofactor'   => 0.8,
            'plusthreefactor' => 0.6,
        ],
        'levels' => [
            'default_min' => 2,
            'default_max' => 30,
        ],

        // Enemy health by key level - see Npc::getScalingFactor() for the formula and #4094 for the combat log
        // measurements (Murder Row at +2, +4..+10, +12) that every number below was fitted to. +1 is the base, every
        // level through +10 adds 7%, every level from +11 adds 10% (Xal'atath's Guile), and the game rounds that
        // per-level multiplier to two decimals before applying the affixes below.
        'scaling_factor'         => 1.07,
        'scaling_factor_past_10' => 1.1,

        'affix_scaling_factor' => [
            'fortified'  => 1.2,
            'tyrannical' => 1.25,
            'thundering' => 1.05,
        ],
        // Fortified (non-bosses) and Tyrannical (bosses): from +10 both are always active, regardless of what the
        // route's affix group says; between +7 and +9 only one of them is, and which one swaps every other week, so
        // there it follows the affixes passed in (the route's affix group). Below +7 neither applies. The #4094
        // measurements at +7..+9 were taken in a Fortified week (trash x1.2, bosses untouched).
        'affix_scaling_factor_min_key_level'      => 7,
        'affix_scaling_factor_both_min_key_level' => 10,

        // Most hostile non-boss enemies have 5% less health at +2..+5 (Lindormi's Guidance, Midnight S2) - measured on
        // every trash NPC of Murder Row and 10 of 14 in The Blinding Vale at +2..+5, absent from +6 on. The affix
        // "weakens select enemies", and which ones is not knowable from our data, so this is the majority behaviour:
        // the minority (and summoned units, which also skip Fortified) is overstated by 5% below +6. A +6 log has
        // neither this nor Fortified in play, which is why combatlog:extractnpchealth prefers one. Set the factor to 1
        // when a season drops the affix.
        'low_key_non_boss_health' => [
            'max_key_level' => 5,
            'factor'        => 0.95,
        ],
    ],

    'cache' => [
        'npcs' => [
            'ttl' => '1 hour',
        ],
        'dungeonData' => [
            'ttl' => '10 minutes',
        ],
        'static_data' => [
            'ttl' => '1 day',
        ],
        'mdt' => [
            'ttl' => '1 hour',
        ],
        'displayed_affix_groups' => [
            'ttl' => '15 minutes',
        ],
        'global_view_variables' => [
            'ttl' => '1 hour',
        ],
        'default_game_region' => [
            'ttl' => '1 hour',
        ],
        'default_game_version' => [
            'ttl' => '1 hour',
        ],
        'raider_io_team' => [
            'ttl' => '1 hour',
        ],
        'mdt_export_strings' => [
            'ttl' => 1800, // 30 minutes
        ],
    ],

    'reverb' => [
        'url'    => env('REVERB_INTERNAL_URL'),
        'port'   => env('REVERB_INTERNAL_PORT'),
        'client' => [
            'app_id' => env('REVERB_APP_ID'),
            'key'    => env('REVERB_APP_KEY'),
        ],

        'randomsuffixes' => [
            // Basilisks
            'Stonegazer',
            // Bats
            'Shrieker',
            'Duskbat',
            // Bears
            'Grizzly',
            'Shardtooth',
            // Birds
            'Falcon',
            'Raven',
            'Seagull',
            // Cat
            'Shadow Stalker',
            'Sabercat',
            'Panther',
            'Frostsaber',
            'Lynx',
            // Clefthoof
            'Clefthoof',
            'Calf',
            'Bull',
            // Crab
            'Sharpclaw',
            'Glimmershell',
            'Crab',
            'Rockshell',
            'Crawler',
            // Devilsaur
            'Devilsaur',
            'Fleshrender',
            // Dog
            'Hound',
            'Darkhound',
            'Watchdog',
            'Mastiff',
            'Deathhound',
            'Felbeast',

            // Can add more here, see https://www.wowhead.com/basilisk-npcs
        ],
    ],

    /** The minimum size for enemies for floors if none was set */
    'min_enemy_size_default' => 12,

    /** The maximum size for enemies for floors if none was set */
    'max_enemy_size_default' => 26,

    /** When generating dungeon routes, this is the maximum range from engagement of an enemy where we consider enemies in the mapping to match up */
    'enemy_engagement_max_range_default' => 150,

    /** The range after which we start considering patrols too */
    'enemy_engagement_max_range_patrols_default' => 50,

    /** The default max zoom level on the map */
    'zoom_max_default' => 5,

    /**
     * Size of a party for a M+ dungeon. Used for a bunch of stuff, changing this value does not mean it's 100% fine though,
     * some layout will need to be re-made for a smaller or higher value.
     */
    'party_size' => 5,

    /**
     * Limits on the relationships that a DungeonRoute can have to ensure performance.
     */
    'dungeon_route_limits' => [
        'kill_zones' => 50,
        'brushlines' => 150,
        'paths'      => 150,
        'arrows'     => 50,
        'map_icons'  => 150,
    ],

    /**
     * The amount of dungeon routes that a normal registered user can make (1 for each dungeon, teeming + non-teeming).
     */
    'registered_user_dungeonroute_limit' => 999,

    /**
     * The amount of time that must pass before a view will be counted again. This is to prevent every single F5 from
     * increasing the view count of a page. When visiting the page, this amount of time in minutes must pass before
     * the view is counted for a second time.
     */
    'view_time_threshold_mins' => 30,

    'page_views' => [
        /** The number of days page view records are kept before being pruned. Only the last X days are needed for popularity calculations. */
        'retention_days' => 30,
    ],

    'thumbnail' => [
        /**
         * A secret key that must be provided to get access to the preview routes (no other auth available)
         */
        'preview_secret' => env('THUMBNAIL_PREVIEW_SECRET'),

        /**
         * When set, the base URL prefixed to the (relative) preview route for puppeteer to
         * navigate to, instead of the app's absolute URL. Needed when the app's public URL isn't
         * reachable from inside the app container itself (e.g. a separate nginx container in dev).
         */
        'preview_base_url' => env('THUMBNAIL_PREVIEW_BASE_URL'),

        /**
         * The amount of time in minutes that must pass before a thumbnail is generated again from a changed dungeon route.
         */
        'refresh_min' => 30,

        /**
         * The amount of hours when a thumbnail refresh must be in the queue for before it is re-queued
         */
        'refresh_requeue_hours' => 12,

        /**
         * The maximum attempts a thumbnail generation can take before it is failed and not queued again
         */
        'max_attempts' => 3,

        /**
         * The maximum amount of thumbnails that will be queued in a single run.
         */
        'refresh_outdated_count' => 10000,
    ],

    /**
     * The amount of hours it takes before a dungeon route that is created through the 'sandbox' functionality expires and
     * is deleted from the server.
     */
    'sandbox_dungeon_route_expires_hours' => 24,

    /**
     * Prideful enemy variables
     */
    'prideful' => [
        'npc_id' => 173729,
        'count'  => 5,
    ],
    'shrouded' => [
        'npc_id'           => 189878,
        'npc_id_zul_gamux' => 190128,
    ],

    /**
     * The creator directory (see the CreatorProfiles feature flag)
     */
    'creators' => [
        /**
         * How many world-published routes a user needs before they are listed automatically.
         * Listing is opt-out: users below this bar never appear, and anyone can remove themselves
         * with the hide_from_creator_directory switch on their profile.
         */
        'min_published_routes' => 3,

        /** How many creators to show per page of the directory */
        'per_page' => 24,

        /** How many creators to feature in the rail - four is what fits its 70rem measure without the shelf needing to scroll on a desktop */
        'featured_count' => 4,

        /** The rail is a site-wide list off a heavy GROUP BY, so it may go this stale - see getFeaturedCreators() */
        'featured_ttl' => '1 hour',
    ],

    /**
     * For the discover section of the site - this controls various variables
     */
    'discover' => [
        /** Limits for how much dungeonroutes to display on certain pages */
        'limits' => [
            'overview'       => 12,
            'category'       => 12,
            'affix_overview' => 12,
            'search'         => 24,
            'per_dungeon'    => 8,
            /** Routes per page on the reworked (DungeonRouteListRework) dungeon leaderboard */
            'leaderboard' => 18,
        ],
        /** How many routes to load more when the user uses the infinite scroll */
        'loadmore' => [
            'count' => 12,
        ],
        'service' => [
            /** Redis prefix */
            'cache_prefix' => 'discover',

            /** The penalty that is applied when the route has an incorrect season. This is multiplicative. */
            'popular_wrong_season_penalty' => 0.25,

            /** The amount of days a pageview may be old for it to be counted towards the 'popular' count */
            'popular_days' => 7,

            /** The amount of days a route can be old before the popularity counter will drop off to 0 */
            'popular_falloff_days' => 60,

            /** The penalty that is applied when the mapping version is out of date. This is multiplicative. */
            'popular_out_of_date_mapping_version_penalty' => 0.25,

            /** Popular routes are cached since they are extra heavy and aren't likely to change much at all */
            'popular' => [
                // Refreshed every 2 hours - cache needs to outlive that
                'ttl' => '3 hours',
            ],

            //            'popular' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'popular_limit_%d',
            //            ],
            //
            //            'popular_by_affix_group' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'popular_by_affix_group_%d',
            //            ],
            //
            //            'popular_by_dungeon' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'popular_by_dungeon_%d',
            //            ],
            //
            //            'popular_by_dungeon_and_affix_group' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'popular_by_dungeon_%d_and_affix_group_%d',
            //            ],
            //
            //
            //            'new' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'popular',
            //            ],
            //
            //            'new_by_affix_group' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'new_by_affix_group_%d',
            //            ],
            //
            //            'new_by_dungeon' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'new_by_dungeon_%d',
            //            ],
            //
            //            'new_by_dungeon_and_affix_group' => [
            //                'ttl'       => '5 min',
            //                'cache_key' => 'new_by_dungeon_%d_and_affix_group_%d',
            //            ],
        ],
    ],

    'view' => [
        'common' => [
            'dungeonroute' => [
                'card' => [
                    'cache' => [
                        'ttl' => '1 hour',
                    ],
                    'allowed_tags' => config('keystoneguru.sanitize_text.allowed_tags'),
                ],
            ],
        ],
    ],

    'live_sessions' => [
        'expires_hours' => 1,
    ],

    'influxdb' => [
        'default_tags' => [
            'environment' => env('APP_ENV'),
        ],
    ],

    'webhook' => [
        'github' => [
            'url'    => env('DISCORD_GITHUB_WEBHOOK'),
            'secret' => env('GITHUB_WEBHOOK_SECRET'),
        ],

        'discord' => [
            'new_release' => [
                'url' => env('DISCORD_NEW_RELEASE_WEBHOOK'),
            ],
        ],

        'raiderio' => [
            'user'     => env('RAIDERIO_WEBHOOK_USER'),
            'password' => env('RAIDERIO_WEBHOOK_PASSWORD'),
        ],
    ],

    'raiderio' => [
        'api_key' => env('RAIDERIO_API_KEY'),
        // Local envs hit the real production RaiderIO API by default. Set this to true to use the
        // local opensearch-backed mock service instead (requires a running local opensearch node).
        'use_local_mock_service' => env('RAIDERIO_USE_LOCAL_MOCK_SERVICE', false),
    ],

    'patreon' => [
        'oauth' => [
            'client_id' => env('PATREON_CLIENT_ID'),
            'secret'    => env('PATREON_CLIENT_SECRET'),
            // https://docs.patreon.com/#scopes
            'scope' => 'identity identity[email] identity.memberships campaigns',
        ],
        'campaign_id' => env('PATREON_CAMPAIGN_ID'),
        // The amount of ad-free giveaways that one may have in total
        'ad_free_giveaways' => 4,
    ],

    'nitro_pay' => [
        'user_id' => env('NITRO_PAY_USER_ID'),
    ],

    'playwire' => [
        'param_1' => env('PLAYWIRE_PARAM_1'),
        'param_2' => env('PLAYWIRE_PARAM_2'),
    ],

    'rollbar' => [
        'client_access_token' => env('ROLLBAR_CLIENT_ACCESS_TOKEN'),
        'server_access_token' => env('ROLLBAR_SERVER_ACCESS_TOKEN'),
    ],

    'cloudflare' => [
        'id' => env('CLOUDFLARE_ID'),
    ],

    'heatmap' => [
        'service' => [
            'data' => [
                // Player data can get away with less accurate positioning
                'player' => [
                    'size_x' => 300,
                    'size_y' => 200,
                ],
                // Enemy requires precise positioning, this resolution is too much
                // for raw since the buckets would be too small, but since the coordinates
                // are equal to enemy positions this only just increases the accuracy of the
                // points, while still having a low bucket count.
                'enemy' => [
                    'size_x' => 300,
                    'size_y' => 200,
                ],
            ],
        ],
        'api' => [
            /*
             * Exclude data points that fall below this factor of the max amount of points in the grid.
             * Say that the top hot spot was 10000 entries, then in order to be included in this heatmap, a data point
             * must have at least 10000 * factor entries in order to be returned. This cuts down on the amount of data
             * being sent by the server to KSG, and KSG to the browser.
             *
             * Set to null to disable.
             */
            'min_required_sample_factor_default' => 0.0005,

            /**
             * Toggles between sending the floors as a continuous array or as key value pairs
             * (["123.456,654.321" => 1234 (when false), ...] vs [123.456, 654.321, 1234, ...] (when true).
             *
             * Null or false disables this
             */
            'floors_as_array' => true,
        ],
    ],

    'api' => [
        'dungeon_route' => [
            'thumbnail' => [
                /**
                 * Per-variant render settings. Each variant is a self-contained set of render
                 * dimensions, so adding a new variant is a matter of data instead of branching code.
                 */
                'variants' => [
                    /** The standard small thumbnail shown in route cards and lists. */
                    'standard' => [
                        'viewport_width'  => 768,
                        'viewport_height' => 512,
                        'image_width'     => 384,
                        'image_height'    => 256,
                        'zoom_level'      => 1,
                        'quality'         => 90,
                        /**
                         * The killzone-path (pull-connection) lines are multiplied by this when rendering
                         * the miniature, so it still reads as a route shape (they'd otherwise be too thin
                         * to see). Null means no thickening.
                         */
                        'kill_zone_path_weight_multiplier' => 3,
                    ],
                    /**
                     * Larger "hero" variant, generated for the top routes only (weekly + top community routes).
                     * Used for the wide, full-width hero band on the discovery page where the small 768x512
                     * render looks stretched. Rendered (and stored) at full size - no downscale. Uses a higher
                     * zoom so the route fills the wide frame instead of being letterboxed, and keeps the normal
                     * (thin) pull-line width since the render is large.
                     */
                    'hero' => [
                        'viewport_width'                   => 1600,
                        'viewport_height'                  => 640,
                        'image_width'                      => 1600,
                        'image_height'                     => 640,
                        'zoom_level'                       => 2.1,
                        'quality'                          => 90,
                        'kill_zone_path_weight_multiplier' => null,
                    ],
                    /**
                     * Custom API-requested renders (viewport/image dimensions come from the request instead
                     * of this config), so only the values still shared with the standard variant live here.
                     * Keep `kill_zone_path_weight_multiplier` in sync with the `standard` variant above -
                     * it is a separate value, not a live reference, so it does not update automatically.
                     */
                    'custom' => [
                        'kill_zone_path_weight_multiplier' => 3,
                    ],
                    /**
                     * Same render dimensions as `standard`, but used only on the front page's "popular this
                     * week" route cards. `standard`'s x3 line weight is tuned to still read as a route shape
                     * at the Find Routes page's small size, but on the front page it just looks thick - this
                     * multiplier sits between `hero` (unmultiplied, but rendered much larger) and `standard`.
                     * Generated alongside the hero variant (thumbnail:ensureheroes), since the front page's
                     * top route per dungeon is already a subset of the routes shown as heroes.
                     */
                    'front_page' => [
                        'viewport_width'                   => 768,
                        'viewport_height'                  => 512,
                        'image_width'                      => 384,
                        'image_height'                     => 256,
                        'zoom_level'                       => 1,
                        'quality'                          => 90,
                        'kill_zone_path_weight_multiplier' => 1.5,
                    ],
                ],
                /** I observed it to be about 8 but with settings it may be longer, so 10 to be safe. */
                'estimated_generation_time_seconds' => 10,
                'expiration_time_seconds'           => 86400,
            ],
        ],
    ],

    'raider_io' => [
        'team_id'            => 2136,
        'combat_log_polling' => [
            // Widened from 1 to 7 (#4035): between seasons there's typically a week with no M+ runs
            // completed at all, and a 1-day window then finds nothing to poll. 7 days still finds
            // last week's runs once M+ activity resumes, so this survives every future season gap
            // too, not just the current one.
            'completed_at_window_days' => (int)env('COMBAT_LOG_POLLING_COMPLETED_AT_WINDOW_DAYS', 7),
            'limit'                    => (int)env('COMBAT_LOG_POLLING_LIMIT', 100),
            'download_url'             => env('COMBAT_LOG_POLLING_DOWNLOAD_URL'),

            // Runs are polled in key level bands so that what we parse covers the whole spectrum
            // instead of the (by far most populous) 10-16 range. One band is polled per hour.
            'bands' => [
                'level_min'         => (int)env('COMBAT_LOG_POLLING_BAND_LEVEL_MIN', 2),
                'width'             => (int)env('COMBAT_LOG_POLLING_BAND_WIDTH', 5),
                'default_threshold' => (int)env('COMBAT_LOG_POLLING_BAND_DEFAULT_THRESHOLD', 60),
            ],

            // The highest keys of a season are always parsed, bypassing the band budgets
            // entirely - those are the runs where players have it all figured out.
            'top_band' => [
                'levels_below_max'    => (int)env('COMBAT_LOG_POLLING_TOP_BAND_LEVELS_BELOW_MAX', 2),
                'min_runs_for_level'  => (int)env('COMBAT_LOG_POLLING_TOP_BAND_MIN_RUNS_FOR_LEVEL', 25),
                'probe_window_days'   => (int)env('COMBAT_LOG_POLLING_TOP_BAND_PROBE_WINDOW_DAYS', 7),
                'probe_level_ceiling' => (int)env('COMBAT_LOG_POLLING_TOP_BAND_PROBE_LEVEL_CEILING', 40),

                // Kept below the hourly schedule of combatlog:pollruns so the max key level is
                // re-probed on every run. At the start of a season everyone starts at the minimum
                // key level and climbs over the following days; a max cached for longer pins the
                // top band's floor near the bottom, and the top band is dispatched without
                // consulting any budget - so it would parse every run of the season.
                'max_key_level_cache_minutes' => (int)env('COMBAT_LOG_POLLING_TOP_BAND_MAX_KEY_LEVEL_CACHE_MINUTES', 50),
            ],
        ],
        'weekly_route' => [
            'url'  => 'https://raider.io/weekly-routes',
            'tags' => [
                'pug_friendly' => env('WEEKLY_ROUTE_TAG_PUG_FRIENDLY', 'pug-friendly-route'),
                'expert'       => env('WEEKLY_ROUTE_TAG_EXPERT', 'expert-route'),
                'title'        => env('WEEKLY_ROUTE_TAG_TITLE', 'title-route'),
            ],
        ],
    ],

    'npc' => [
        /**
         * NPCs whose seeded data is hand-curated and must never be replaced by an automated source. Honoured by two
         * paths, with different breadth: MDTMappingImportService skips the NPC's data entirely (health, display id,
         * encounter id, ...), while combatlog:extractnpchealth skips only its npc_healths row - even with --overwrite -
         * and reports it as "curated".
         *
         * @var array<int, int>
         */
        'curated_npc_data_npc_ids' => [
            // Priory of the Sacred Flame - 3 mini bosses where MDT has high health values - they mess up auto map sizing based on health
            211289,
            211290,
            211291,
            // Murder Row - Infernal, stored at 2.7M while it has ~205M in game: a 200M trash mob breaks the
            // health-based enemy sizing on the map (Wotuu, #4207)
            238414,
            // King's Rest - Minion of Zul, a gimmick mob that carries a shield and deliberately low health. Combat
            // logs show a flat 24 max HP that does not scale with the key level at all, so no base health can be
            // derived from them (Wotuu, #4208) - the seeded value stands.
            133943,
            138493,
        ],
    ],

    'mdt' => [
        'version' => 'v6.2.3',
    ],

    'combat_log_route_regeneration' => [
        'user'     => env('COMBAT_LOG_ROUTE_REGENERATION_USER'),
        'password' => env('COMBAT_LOG_ROUTE_REGENERATION_PASSWORD'),
    ],

    'combat_log_staleness' => [
        /** The number of days without a new observation before an NPC characteristic or spell property is considered stale and removed. */
        'observation_window_days' => (int)env('COMBAT_LOG_STALENESS_OBSERVATION_WINDOW_DAYS', 3),
    ],
];
