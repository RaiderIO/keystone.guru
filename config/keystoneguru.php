<?php


return [
    'super_admins' => [
        'Admin',
        'gallypsa'
    ],

    'github_username' => 'Wotuu',

    'github_repository' => 'Keystone.guru',

    'reddit_subreddit' => 'KeystoneGuru',

    'cache'                             => [
        'npcs'        => [
            'ttl' => '1 hour'
        ],
        'dungeonData' => [
            'ttl' => '1 hour'
        ],
        'static_data' => [
            'ttl' => '1 day'
        ]
    ],

    'echo' => [
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

    /**
     * The amount of time in minutes that must pass before a thumbnail is generated again from a changed dungeon route.
     */
    'thumbnail_refresh_min'              => 30,

    /**
     * The amount of hours it takes before a dungeon route that is created through the 'try' functionality expires and
     * is deleted from the server.
     */
    'try_dungeon_route_expires_hours'    => 24,

    /**
     * @var array List of current roles for a user in a team.
     */
    'team_roles'                         => ['member' => 1, 'collaborator' => 2, 'moderator' => 3, 'admin' => 4]
];