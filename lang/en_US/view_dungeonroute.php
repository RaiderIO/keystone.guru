<?php

return [

    'discover' => [
        'dungeon' => [
            'overview' => [
                'weekly_route'               => 'Raider.IO Weekly Route',
                'popular'                    => 'Popular routes',
                'popular_by_current_affixes' => 'Popular routes by current affixes',
                'popular_by_next_affixes'    => 'Popular routes by next affixes',
                'newly_published_routes'     => 'Newly published routes',
                'featured_title'             => 'Featured routes',
                'featured'                   => [
                    'pug_friendly' => 'PUG Route',
                    'expert'       => 'Expert Route',
                    'title'        => 'Title Route',
                ],
                'stats' => [
                    'groups'      => 'Groups',
                    'avg_enemies' => 'Avg. enemies per group',
                ],
                'compendium' => [
                    'title'    => 'Know the dungeon',
                    'subtitle' => 'Powered by community combat logs and kept up to date automatically.',
                    'npc'      => [
                        'title'        => 'NPCs',
                        'description'  => 'Browse every NPC in this dungeon, their abilities and characteristics.',
                        'cta'          => 'View NPCs',
                        'count_suffix' => 'NPCs',
                    ],
                    'spell' => [
                        'title'        => 'Spells',
                        'description'  => 'See the spells and abilities enemies cast in this dungeon.',
                        'cta'          => 'View spells',
                        'count_suffix' => 'Spells',
                    ],
                    'activity' => [
                        'title'       => 'Recent activity',
                        'description' => 'Follow the latest runs and activity recorded for this dungeon.',
                        'cta'         => 'View activity',
                        'subtitle'    => 'Updated daily',
                    ],
                ],
                'your_routes' => 'Your routes',
                'browse_all'  => 'Browse all routes',
            ],
        ],
        'discover' => [
            'title'                      => 'Routes',
            'popular'                    => 'Popular routes',
            'popular_by_current_affixes' => 'Popular routes by current affixes',
            'popular_by_next_affixes'    => 'Popular routes by next affixes',
            'newly_published_routes'     => 'Newly published routes',
        ],
        'panel' => [
            'show_more' => 'Show more',
        ],
        'search' => [
            'page_title'              => 'Search routes',
            'header'                  => 'Search routes',
            'title'                   => 'Title',
            'title_placeholder'       => 'Filter by title',
            'key_level'               => 'Key level',
            'affixes'                 => 'Affixes',
            'affixes_title'           => 'Select affixes',
            'select_affixes'          => 'Select affixes',
            'affixes_selected'        => '{0} affixes selected',
            'enemy_forces'            => 'Enemy forces',
            'enemy_forces_complete'   => 'Complete',
            'enemy_forces_incomplete' => 'Incomplete',
            'rating'                  => 'Rating',
            'user'                    => 'User',
            'user_placeholder'        => 'Filter by user',
        ],
    ],
    'livesession' => [
        'title' => 'Live session - :title',
        'view'  => [
            'any' => 'Any',
        ],
    ],
    'edit' => [
        'title'                                   => 'Edit %s',
        'linkpreview_title'                       => '%s | Keystone.guru',
        'linkpreview_default_description'         => 'Edit M+ route for dungeon %s by %s',
        'linkpreview_default_description_sandbox' => 'Edit M+ route for dungeon %s',
    ],
    'embed' => [
        'title'            => 'Embed :routeTitle',
        'any'              => 'Any',
        'select_floor'     => 'Select floor',
        'affixes_title'    => 'Affixes',
        'affixes_selected' => '{0} affixes selected',
        'view_route'       => 'View route',
        'present_route'    => 'Present route',
        'copy_mdt_string'  => 'Copy MDT',
    ],
    'limitreached' => [
        'title'                     => 'Limit reached',
        'header'                    => 'Limit reached',
        'limit_reached_description' => 'You have reached the maximum amount of routes you may create (%s). Please consider becoming a Patron to continue making more routes, or delete some of your existing routes. Thank you for using the site!',
        'become_a_patreon'          => 'Become a %s Patron!',
    ],
    'new' => [
        'title' => 'New route',
    ],
    'newtemporary' => [
        'title'  => 'Create temporary route',
        'header' => 'New temporary route',
    ],
    'sandboxclaimed' => [
        'title'               => 'Route already claimed',
        'header'              => 'Route already claimed',
        'claimed_description' => 'This route has already been claimed by someone (or you used the back button in your browser to navigate here).',
    ],
    'unavailable' => [
        'title'                   => 'Unpublished route',
        'unavailable_description' => 'You are not authorized to view this route. Ask the author of the route to change the route\'s Sharing settings so that you can view it.',
    ],
    'view' => [
        'any'                                     => 'Any',
        'linkpreview_title'                       => '%s',
        'linkpreview_default_description'         => 'M+ route for dungeon %s by %s.',
        'linkpreview_default_description_sandbox' => 'Temporary M+ route for dungeon %s.',
        'linkpreview_default_description_explore' => 'Explore %s.',
    ],

];
