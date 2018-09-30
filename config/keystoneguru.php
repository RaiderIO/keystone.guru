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
     * The amount of dungeon routes that a normal registered user can make (1 for each dungeon, teeming + non-teeming)
     */
    'registered_user_dungeonroute_limit' => 18,

    ''
];