<?php

return [

    'explore' => [
        'gameversion' => [
            'list'  => [
                'title'             => 'Explore',
                'header'            => 'Explore dungeon',
                'description'       => 'Exploring a dungeon allows you to see the layout of the dungeon and the enemies that are present. Ideal for simply viewing the dungeon without creating a route.',
                'heatmap_available' => 'Heatmap available for dungeon',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Any',
                'select_floor'            => 'Select floor',
                'view_heatmap_fullscreen' => 'View fullscreen',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list'  => [
                'title'             => 'Heatmaps',
                'header'            => 'Dungeon heatmaps',
                'raider_io'         => 'Raider.IO',
                'description'       => 'Powered by :raiderIO, heatmaps can show you invaluable information about which enemies are slain by players, where they are getting themselves killed or are casting certain spells. Filters for key level, item level, team composition and many more allow you to focus on the data that is relevant for your needs.',
                'heatmap_available' => 'Heatmap available for dungeon',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Any',
                'select_floor'            => 'Select floor',
                'view_heatmap_fullscreen' => 'View fullscreen',
            ],
        ],
    ],

];
