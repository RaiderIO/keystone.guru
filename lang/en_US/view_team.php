<?php

return [
    'edittabs' => [
        'details'         => [
            'title' => 'Team details',
        ],
        'members'         => [
            'title' => 'Members',

            'invite_new_members'        => 'Invite new members',
            'invite_code_share_warning' => 'Be careful who you share the invite link with, everyone with the link can join your team!',
            'copy_to_clipboard_title'   => 'Copy to clipboard',
            'refresh_invite_link_title' => 'Refresh invite link',

            'default_role' => 'Default role',

            'ad_free_giveaway_description_not_available' => 'Subscribing to :patreon allows you to give away up to :max ad-free experiences to Keystone.guru to any team member.',
            'ad_free_giveaway_description_available'     => 'Thank you for subscribing to Keystone.guru\'s :patreon! You can give away :current more ad-free experiences to Keystone.guru to any team member.',
        ],
        'overview'        => [
            'title' => 'Overview',
        ],
        'routepublishing' => [
            'title' => 'Route publishing',
        ],
        'routes'          => [
            'title'                  => 'Route list',
            'add_route'              => 'Add route',
            'add_route_no_moderator' => 'You must be a Moderator of this team to add routes',
            'stop_adding_routes'     => 'Stop adding routes',
        ],
        'tags'            => [
            'title'       => 'Tags',
            'description' => 'You can manage tags for the team\'s routes here. Everyone that is a member of this team may view the tags attached to the routes.
                        The personal tags that may or may not have been attached by the route owner will not be visible.',
        ],

    ],
    'edit'     => [
        'title'          => 'Team %s',
        'menu_title'     => 'Teams',
        'to_team_list'   => 'Team list',
        'team_header'    => 'Team %s',
        'icon_image_alt' => 'No image',
        'routes'         => 'Routes',
        'members'        => 'Members',
    ],
    'invite'   => [
        'linkpreview_title'       => 'Invitation to join team %s',
        'linkpreview_description' => 'You have been invited to join team %s. Login or register on Keystone.guru to join the team, it\'s free!',

        'title'        => 'Invitation to join team %s',
        'header'       => 'Invitation to join team %s',
        'invalid_team' => 'Invalid team',

        'logo_image_alt'              => 'Team logo',
        'already_a_member'            => 'You are already a member of team %s!',
        'invited_to_join'             => 'You have been invited to join team %s.',
        'accept_the_invitation'       => 'Accept the invitation to join the team!',
        'login_or_register_to_accept' => 'Login or register on Keystone.guru to join the team, it\'s free!',

        'return_to_team'    => 'Return to team',
        'accept_invitation' => 'Accept invitation',
        'login'             => 'Login',
        'register'          => 'Register',

        'invite_not_found' => 'This team could not be found. Perhaps the invite link has been changed or the team has been deleted.',
        'back_to_homepage' => 'Back to the home page',
    ],
    'list'     => [
        'title'                => 'My teams',
        'header'               => 'My teams',
        'create_team'          => 'Create team',
        'table_header_team'    => 'Team',
        'table_header_members' => 'Members',
        'table_header_routes'  => 'Routes',
    ],
    'new'      => [
        'title'  => 'New team',
        'header' => 'New team',
    ],
];
