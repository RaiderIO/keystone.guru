<?php


return [
    'super_admins' => [
        'Admin',
        'gallypsa',
    ],

    'github_username' => 'Wotuu',

    'github_repository' => 'Keystone.guru',

    'reddit_subreddit' => 'KeystoneGuru',

    'timer' => [
        'plustwofactor'   => 0.8,
        'plusthreefactor' => 0.6,
    ],

    'levels' => [
        'min' => 2,
        'max' => 28
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
            'ttl' => '1 day',
        ],
    ],

    'echo'                              => [
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
        ]
    ],

    /**
     * The minimum size for enemies for floors if none was set
     */
    'min_enemy_size_default'            => 12,

    /**
     * The maximum size for enemies for floors if none was set
     */
    'max_enemy_size_default'            => 26,

    /**
     * The amount of hours it takes after changes have occurred, before they're automatically synced with the server.
     * This prevents active mapping efforts from getting commits every 2 minutes or something
     */
    'mapping_commit_after_change_hours' => 1,

    /**
     * Size of a party for a M+ dungeon. Used for a bunch of stuff, changing this value does not mean it's 100% fine though,
     * some layout will need to be re-made for a smaller or higher value.
     */
    'party_size'                        => 5,

    /**
     * States of aggressiveness of NPCs. Aggressive = will aggro upon getting close, unfriendly = will not aggro,
     * but will soon turn aggressive (not sure if it's going to be used), neutral = will not aggro unless provoked.
     */
    'aggressiveness'                    => ['aggressive', 'unfriendly', 'neutral', 'friendly', 'awakened'],

    'aggressiveness_pretty'              => ['Aggressive', 'Unfriendly', 'Neutral', 'Friendly', 'Awakened'],

    /**
     * Some playful names for difficulties. I didn't want to offend anyone (looking at you non-casuals).
     */
    'dungeonroute_difficulty'            => ['Casual', 'Dedicated', 'Hardcore'],

    /**
     * The amount of dungeon routes that a normal registered user can make (1 for each dungeon, teeming + non-teeming).
     */
    'registered_user_dungeonroute_limit' => 999,


    /**
     * How many affix groups are in an iteration of a season.
     */
    'season_iteration_affix_group_count' => 12,

    /**
     * The amount of time that must pass before a view will be counted again. This is to prevent every single F5 from
     * increasing the view count of a page. When visiting the page, this amount of time in minutes must pass before
     * the view is counted for a second time.
     */
    'view_time_threshold_mins'           => 30,

    'thumbnail'                           => [
        /**
         * A secret key that must be provided to get access to the preview routes (no other auth available)
         */
        'preview_secret'       => env('THUMBNAIL_PREVIEW_SECRET'),

        /**
         * The amount of time in minutes that must pass before a thumbnail is generated again from a changed dungeon route.
         */
        'refresh_min'          => 30,

        /**
         * The amount of days where the thumbnail gets refreshed anyways regardless of other rules.
         */
        'refresh_anyways_days' => 30,
    ],

    /**
     * The amount of hours it takes before a dungeon route that is created through the 'sandbox' functionality expires and
     * is deleted from the server.
     */
    'sandbox_dungeon_route_expires_hours' => 24,

    /**
     * @var array List of current roles for a user in a team.
     */
    'team_roles'                          => ['member' => 1, 'collaborator' => 2, 'moderator' => 3, 'admin' => 4],

    /**
     * @var array Prideful enemy variables
     */
    'prideful'                            => [
        'npc_id' => 173729,
        'count'  => 5
    ],

    /**
     * For the discover section of the site - this controls various variables
     */
    'discover'                            => [
        /** Limits for how much dungeonroutes to display on certain pages */
        'limits'   => [
            'overview'       => 10,
            'category'       => 20,
            'affix_overview' => 10,
            'search'         => 20,
        ],
        /** How many routes to load more when the user uses the infinite scroll */
        'loadmore' => [
            'count' => 20,
        ],
        'service'  => [
            /** Redis prefix */
            'cache_prefix' => 'discover',

            /** The amount of days a pageview may be old for it to be counted towards the 'popular' count */
            'popular_days' => 7,

            /** Popular routes are cached since they are extra heavy and aren't likely to change much at all */
            'popular'      => [
                'ttl' => '1 hour',
            ]


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
        ]
    ],

    'view' => [
        'common.dungeonroute.card' => [
            'cache' => [
                'ttl' => '1 hour'
            ]
        ]
    ],

    'influxdb' => [
        'default_tags' => [
            'environment' => env('APP_ENV')
        ]
    ],

    'webhook' => [
        'github' => [
            'url'    => env('DISCORD_GITHUB_WEBHOOK'),
            'secret' => env('GITHUB_WEBHOOK_SECRET')
        ],

        'discord' => [
            'new_release' => [
                'url' => env('DISCORD_NEW_RELEASE_WEBHOOK')
            ]
        ]
    ],

    'patreon' => [
        'oauth' => [
            'client_id' => env('PATREON_CLIENT_ID'),
            'secret'    => env('PATREON_CLIENT_SECRET'),
        ]
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
    ]
];