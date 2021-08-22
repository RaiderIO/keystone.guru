<?php

return [
    'affixgroup'   => [
        'affixgroup' => [

        ]
    ],
    'dungeon'      => [
        'griddiscover' => [
            'popular'   => 'Popular',
            'this_week' => 'This week',
            'next_week' => 'Next week',
            'new'       => 'New',
        ],
        'select'       => [
            'siege_of_boralus_warning' => 'Due to differences between the Horde and the Alliance version of Siege of Boralus, you are required to select a faction in the group composition.'
        ]
    ],
    'dungeonroute' => [
        'search'     => [
            'loadmore' => [
                'loading' => 'Loading...',
            ]
        ],
        'attributes' => [
            'attributes'              => 'Attributes',
            'no_attributes_title'     => 'Select the attributes that your group is comfortable with handling.',
            'select_attributes_title' => 'Attributes describe what features your route has that others may not be able to complete due to composition ' .
                'differences or skill. Marking attributes properly enables others to find routes that fit them more easily.',
            'attributes_selected'     => '{0} attributes'
        ],
        'card'       => [
            'no_description'    => 'No description',
            'by_author'         => 'By',
            'updated_at'        => 'Updated %s',
            'report'            => 'Report',
            'refresh_thumbnail' => 'Refresh thumbnail'
        ],
        'rating'     => [
            'nr_of_votes' => '%s vote(s)'
        ],
        'table'      => [
            'affixes_selected'   => '{0} affixes selected',
            'requirements'       => 'Requirements',
            'enemy_enemy_forces' => 'Enough enemy forces',
            'favorite'           => 'Favorite'
        ],
        'tier'       => [
            'data_by_subcreation' => '%s - data by https://mplus.subcreation.net',
        ]
    ],
    'forms'        => [
        'createroute'          => [
            'title'             => 'Title',
            'title_title'       => 'Choose a title that will uniquely identify the route for you over other similar routes you may create. The title will be visible to others once you choose to publish your route.',
            'description'       => 'Description',
            'description_title' => 'An optional description of your route. The description will be visible to others once you choose to publish your route. You can always edit your description later.',
            'key_levels'        => 'Key levels',
            'key_levels_title'  => 'Indicate for which key levels your route is suited. This can help others find your route more easily.',
            'advanced_options'  => 'Advanced options',
            'affixes'           => 'Affixes',
            'group_composition' => 'Group composition',
            'admin'             => 'Admin',
            'demo_route'        => 'Demo route',
            'create_route'      => 'Create route',
            'save_settings'     => 'Save settings',
        ],
        'createtemporaryroute' => [
            'unregistered_user_message' => 'As an unregistered user, all created routes will be temporary routes which expire after %d hours.',
            'registered_user_message'   => 'A temporary route will not show up in your profile and will be deleted automatically after %d hours unless it is claimed before that time.',
            'create_route'              => 'Create route',
        ],
        'login'                => [
            'login'                => 'Login',
            'email_address'        => 'E-mail address',
            'password'             => 'Password',
            'remember_me'          => 'Remember me',
            'forgot_your_password' => 'Forgot your password?',
            'login_through_oauth2' => 'Login through OAuth2',
        ],
        'mapsettings'          => [
            'enemies'                                => 'Enemies',
            'enemy_number_style'                     => 'Enemy number style',
            'enemy_number_style_title'               => 'This controls what the numbers mean when you mouse over enemies or when you select the \'Enemy forces\' display type.',
            'percentage'                             => 'Percentage',
            'enemy_forces'                           => 'Enemy forces',
            'unkilled_enemy_opacity'                 => 'Unkilled enemy opacity',
            'unkilled_enemy_opacity_title'           => 'This option allows you to fade out enemies that are not part of any of your pulls. This can reduce the clutter of enemies you want to ignore.',
            'unkilled_important_enemy_opacity'       => 'Unkilled important enemy opacity',
            'unkilled_important_enemy_opacity_title' => 'Important enemies are those that are either Prideful, marked as Inspiring or are marked as required.
                    These can be rendered at a different opacity than other enemies to highlight their importance should you reduce the opacity of all other enemies.',
            'show_aggressiveness_border'             => 'Show aggressiveness border',
            'show_aggressiveness_border_title'       => 'Enabling this setting will render all enemies with a border to indicate their aggressiveness. Red for aggressive enemies, yellow for neutral enemies, green for friendly enemies etc.',
            'highlight_dangerous_enemies'            => 'Highlight dangerous enemies',
            'highlight_dangerous_enemies_title'      => 'Dangerous enemies are marked with a dotted orange inner border. These enemies are hand-picked by Keystone.guru.
                        These enemies are mini-bosses, those with high health compared to others, use dangerous abilities or otherwise require special care.',
            'drawing'                                => 'Drawing',
            'default_line_weight'                    => 'Default line weight',
            'default_line_weight_title'              => 'This controls the default weight (width) of any lines you create on the map, such as paths and free drawn lines.',
        ], 'mdtimport'         => [
            'paste_mdt_export_string'                => 'Paste your Mythic Dungeon Tools export string',
            'reset_title'                            => 'Reset',
            'unregistered_user_all_routes_temporary' => 'As an unregistered user, all imported routes will be temporary routes which expire after %s hours.',
            'temporary_route'                        => 'Temporary route',
            'temporary_route_title'                  => 'A temporary route will not show up in your profile and will be deleted automatically after %d hours unless it is claimed before that time.',
            'parsing_your_string'                    => 'Parsing your string...',
            'import_route'                           => 'Import route',
        ],
        'oauth'                => [

        ],
        'pullsettings'         => [
            'pull_number_style'                 => 'Pull number style',
            'pull_number_style_title'           => 'This controls how the pulls sidebar displays numbers.',
            'pull_number_style_percentage'      => 'Percentage',
            'pull_number_style_enemy_forces'    => 'Enemy forces',
            'show_floor_breakdown'              => 'Show floor breakdown',
            'show_floor_breakdown_title'        => 'This displays the visibility of the floor breakdown in the sidebar as your pulls transition across the dungeon.',
            'pull_color_gradient'               => 'Pull color gradient',
            'pull_color_gradient_title'         => 'Setting a pull gradient will allow Keystone.guru to automatically color your pulls along a gradient.
                    Using this feature you can more easily see which pull belongs to which part of the route, useful for non-linear routes alike. This setting is unique per route.',
            'apply_now'                         => 'Apply now',
            'apply_now_title'                   => 'Apply to current pulls',
            'always_apply_on_pull_change'       => 'Always apply when I change pulls',
            'always_apply_on_pull_change_title' => 'Enabling this setting will update your pull\'s colors as you edit your pulls based on the pull gradient configured above. This setting is unique per route.',
        ],
        'register'             => [
            'register'                => 'Register',
            'username'                => 'Username',
            'username_title'          => 'Your username may be visible if you choose to publish any routes you make.',
            'email_address'           => 'E-mail address',
            'email_address_title'     => 'Your e-mail address will be required upon login. Your e-mail address will not be validated - but it will be used if you ever forget your password to e-mail you a password reset link.',
            'select_region'           => 'Select region',
            'region'                  => 'Region',
            'password'                => 'Password',
            'confirm_password'        => 'Confirm password',
            'legal_agree'             => 'I have read and agree with the %s, %s and the %s.',
            'register_through_oauth2' => 'Register through OAuth2',
            'legal_agree_oauth2'      => 'By registering through OAuth2, you declare that you have read and agree with the %s, %s and the %s.',
            'terms_of_service'        => 'terms of service',
            'privacy_policy'          => 'privacy policy',
            'cookie_policy'           => 'cookie policy',
        ],
        'timezoneselect'       => [
            'africa'     => 'Africa',
            'america'    => 'America',
            'antarctica' => 'Antarctica',
            'asia'       => 'Asia',
            'atlantic'   => 'Atlantic',
            'europe'     => 'Europe',
            'indian'     => 'Indian',
            'pacific'    => 'Pacific',
            'timezone'   => 'Timezone',
        ],
    ],
    'group'        => [
        'affixes'     => [
            'awakened_enemy_set'       => 'Awakened enemy set',
            'awakened_enemy_set_title' => 'Awakened enemies (pillar bosses) for M+ levels 10 and higher come in two sets. Each set of affixes is marked either A or B.
    You may attach multiple affixes to your route whom can have both A and B sets. Choose here which set will be displayed on the map.
    You can always adjust your selection from the Route Settings menu later.',
            'tormented_preset'         => 'Tormented preset',
            'tormented_preset_title'   => 'Tormented enemies for M+ levels 10 and higher come in %s presets.
    You may attach multiple affixes to your route whom can contain any combination of presets. Choose here which preset will be displayed on the map.
    You can always adjust your selection from the Route Settings menu later.'
        ],
        'composition' => [
            'faction'         => 'Faction',
            'undo'            => 'Undo',
            'party_member_nr' => 'Party member #%d'
        ]
    ],
    'layout'       => [
        'footer' => [
            'about'         => 'About',
            'changelog'     => 'Changelog',
            'changelog_new' => 'NEW',
            'credits'       => 'Credits',
            'external'      => 'External',
            'patreon'       => 'Patreon',
            'discord'       => 'Discord',
            'github'        => 'Github',
            'legal' => 'Legal',
            'terms_of_service' => 'Terms of Service',
            'privacy_policy' => 'Privacy Policy',
            'cookie_policy' => 'Cookie Policy',
            'trademark' => 'Trademark',
            'trademark_footer' => 'World of Warcraft, Warcraft and Blizzard Entertainment are trademarks or registered
                        trademarks of Blizzard Entertainment, Inc. in the U.S. and/or other countries. This website is not
                        affiliated with Blizzard Entertainment.',
            'social' => 'Social',
            'all_rights_reserved' => '©:date :nameAndVersion - All Rights Reserved',
        ],
    ]

];
