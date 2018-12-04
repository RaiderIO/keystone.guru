<?php


return [
    /**
     * Size of a party for a M+ dungeon. Used for a bunch of stuff, changing this value does not mean it's 100% fine though,
     * some layout will need to be re-made for a smaller or higher value.
     */
    'party_size' => 5,

    /**
     * States of aggressiveness of NPCs. Aggressive = will aggro upon getting close, unfriendly = will not aggro,
     * but will soon turn aggressive (not sure if it's going to be used), neutral = will not aggro unless provoked.
     */
    'aggressiveness' => ['aggressive', 'unfriendly', 'neutral', 'friendly'],

    'aggressiveness_pretty' => ['Aggressive', 'Unfriendly', 'Neutral', 'Friendly'],

    /**
     * Some playful names for difficulties. I didn't want to offend anyone (looking at you non-casuals).
     */
    'dungeonroute_difficulty' => ['Casual', 'Dedicated', 'Hardcore'],

    /**
     * The amount of dungeon routes that a normal registered user can make (1 for each dungeon, teeming + non-teeming).
     */
    'registered_user_dungeonroute_limit' => 18,


    /**
     * The year in which the season started.
     */
    'season_start_year' => 2018,

    /**
     * Which week the current affix season has started at.
     */
    'season_start_week' => 36,

    /**
     * We require at least 2 more yes votes than no votes before an enemy is marked as Infested on the map.
     * This may need to be increased in the future as the site becomes more popular.
     */
    'infested_user_vote_threshold' => 2,

    /**
     * The amount of time that must pass before a view will be counted again. This is to prevent every single F5 from
     * increasing the view count of a page. When visiting the page, this amount of time in minutes must pass before
     * the view is counted for a second time.
     */
    'view_time_threshold_mins' => 30,

    /**
     * The amount of time to give the route thumbnail processor to complete its processing.
     */
    'route_thumbnail_virtual_time_budget' => 10000
];