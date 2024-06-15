<?php

return [
    // This is really only to give some admins more power than other admins - there's no point in changing this for nefarious reasons
    'super_admins' => [
        'Admin',
    ],

    'db_backup_dir'      => env('DB_BACKUP_DIR'),
    'mapping_backup_dir' => env('MAPPING_BACKUP_DIR'),

    'github_username' => 'Wotuu',

    'github_repository' => 'Keystone.guru',

    'reddit_subreddit' => 'KeystoneGuru',

    'character' => [
        /** // https://wowpedia.fandom.com/wiki/Movement */
        'default_movement_speed_yards_second' => 7,
        'mounted_movement_speed_yards_second' => 14,
        'mount_cast_time_seconds'             => 1.5,
    ],

    'keystone' => [
        'timer'  => [
            'plustwofactor'   => 0.8,
            'plusthreefactor' => 0.6,
        ],
        'levels' => [
            'default_min' => 2,
            'default_max' => 30,
        ],

        'scaling_factor'         => 1.08,
        'scaling_factor_past_10' => 1.10,
    ],

    'cache' => [
        'npcs'                   => [
            'ttl' => '1 hour',
        ],
        'dungeonData'            => [
            'ttl' => '1 hour',
        ],
        'static_data'            => [
            'ttl' => '1 day',
        ],
        'mdt'                    => [
            'ttl' => '1 day',
        ],
        'displayed_affix_groups' => [
            'ttl' => '15 minutes',
        ],
        'global_view_variables'  => [
            'ttl' => '1 hour',
        ],
        'default_region'         => [
            'ttl' => '1 week',
        ],
    ],

    'echo'                                       => [
        'url'    => env('LARAVEL_ECHO_SERVER_URL'),
        'port'   => env('LARAVEL_ECHO_SERVER_PORT'),
        'client' => [
            'app_id' => env('LARAVEL_ECHO_SERVER_CLIENT_APP_ID'),
            'key'    => env('LARAVEL_ECHO_SERVER_CLIENT_KEY'),
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
    'min_enemy_size_default'                     => 12,

    /** The maximum size for enemies for floors if none was set */
    'max_enemy_size_default'                     => 26,

    /** When generating dungeon routes, this is the maximum range from engagement of an enemy where we consider enemies in the mapping to match up */
    'enemy_engagement_max_range_default'         => 150,

    /** The range after which we start considering patrols too */
    'enemy_engagement_max_range_patrols_default' => 50,

    /** The default max zoom level on the map */
    'zoom_max_default'                           => 5,

    /**
     * The amount of hours it takes after changes have occurred, before they're automatically synced with the server.
     * This prevents active mapping efforts from getting commits every 2 minutes or something
     */
    'mapping_commit_after_change_hours'          => 1,

    /**
     * Size of a party for a M+ dungeon. Used for a bunch of stuff, changing this value does not mean it's 100% fine though,
     * some layout will need to be re-made for a smaller or higher value.
     */
    'party_size'                                 => 5,

    /**
     * Limits on the relationships that a DungeonRoute can have to ensure performance.
     */
    'dungeon_route_limits'                       => [
        'kill_zones' => 50,
        'brushlines' => 50,
        'paths'      => 50,
        'map_icons'  => 50,
    ],

    /**
     * The amount of dungeon routes that a normal registered user can make (1 for each dungeon, teeming + non-teeming).
     */
    'registered_user_dungeonroute_limit'         => 999,

    /**
     * The amount of time that must pass before a view will be counted again. This is to prevent every single F5 from
     * increasing the view count of a page. When visiting the page, this amount of time in minutes must pass before
     * the view is counted for a second time.
     */
    'view_time_threshold_mins'                   => 30,

    'thumbnail'                           => [
        /**
         * A secret key that must be provided to get access to the preview routes (no other auth available)
         */
        'preview_secret'         => env('THUMBNAIL_PREVIEW_SECRET'),

        /**
         * The amount of time in minutes that must pass before a thumbnail is generated again from a changed dungeon route.
         */
        'refresh_min'            => 30,

        /**
         * The amount of days where the thumbnail gets refreshed anyways regardless of other rules.
         */
        'refresh_anyways_days'   => 30,

        /**
         * The amount of hours where a thumbnail refresh must be in the queue for before it is re-queued
         */
        'refresh_requeue_hours'  => 12,

        /**
         * The maximum attempts a thumbnail generation can take before it is failed and not queued again
         */
        'max_attempts'           => 3,

        /**
         * The maximum amount of thumbnails that will be queued in a single run.
         */
        'refresh_outdated_count' => 300,
    ],

    /**
     * The amount of hours it takes before a dungeon route that is created through the 'sandbox' functionality expires and
     * is deleted from the server.
     */
    'sandbox_dungeon_route_expires_hours' => 24,

    /**
     * Prideful enemy variables
     */
    'prideful'                            => [
        'npc_id' => 173729,
        'count'  => 5,
    ],
    'shrouded'                            => [
        'npc_id'           => 189878,
        'npc_id_zul_gamux' => 190128,
    ],

    /**
     * For the discover section of the site - this controls various variables
     */
    'discover'                            => [
        /** Limits for how much dungeonroutes to display on certain pages */
        'limits'   => [
            'overview'       => 12,
            'category'       => 24,
            'affix_overview' => 12,
            'search'         => 24,
            'per_dungeon'    => 4,
        ],
        /** How many routes to load more when the user uses the infinite scroll */
        'loadmore' => [
            'count' => 12,
        ],
        'service'  => [
            /** Redis prefix */
            'cache_prefix'                                => 'discover',

            /** The amount of days a pageview may be old for it to be counted towards the 'popular' count */
            'popular_days'                                => 7,

            /** The amount of days a route can be old before the popularity counter will drop off to 0 */
            'popular_falloff_days'                        => 30,

            /** The penalty that is applied when the mapping version is out of date. This is multiplicative. */
            'popular_out_of_date_mapping_version_penalty' => 0.25,

            /** Popular routes are cached since they are extra heavy and aren't likely to change much at all */
            'popular'                                     => [
                'ttl' => '2 hours',
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
        'common.dungeonroute.card' => [
            'cache' => [
                'ttl' => '1 hour',
            ],
        ],
    ],

    'live_sessions' => [
        'expires_hours' => 1,
    ],

    'releases' => [
        'spotlight_show_days' => 7,
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
    ],

    'patreon' => [
        'oauth'             => [
            'client_id' => env('PATREON_CLIENT_ID'),
            'secret'    => env('PATREON_CLIENT_SECRET'),
            // https://docs.patreon.com/#scopes
            'scope'     => 'identity identity[email] identity.memberships campaigns',
        ],
        'campaign_id'       => env('PATREON_CAMPAIGN_ID'),
        // The amount of ad-free giveaways that one may have in total
        'ad_free_giveaways' => 4,
    ],

    'reddit' => [
        'oauth' => [
            'client_id' => env('REDDIT_CLIENT_ID'),
            'secret'    => env('REDDIT_SECRET_KEY'),
        ],
        // Used for creating release posts under the Keystoneguru user
        'api'   => [
            'refresh_token' => env('REDDIT_REFRESH_TOKEN'),
        ],
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

    'heatmap' => [
        'service' => [
            'data' => [
                'sizeX' => 800,
                'sizeY' => 600,
            ],
        ],
    ],

    'api' => [
        'dungeon_route' => [
            'thumbnail' => [
                'default_viewport_width'            => 768,
                'default_viewport_height'           => 512,
                'default_image_width'               => 384,
                'default_image_height'              => 256,
                'default_zoom_level'                => 1,
                'default_quality'                   => 90,
                /** I observed it to be about 8 but with settings it may be longer, so 10 to be safe. */
                'estimated_generation_time_seconds' => 10,
                'expiration_time_seconds'           => 86400,
            ],
        ],
    ],
];
