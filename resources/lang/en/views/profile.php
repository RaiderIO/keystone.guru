<?php

return [
    'edit'      => [
        'title'                   => 'Profile',
        'profile'                 => 'Profile',
        'account'                 => 'Account',
        'patreon'                 => 'Patreon',
        'change_password'         => 'Change password',
        'privacy'                 => 'Privacy',
        'reports'                 => 'Reports',
        'menu_title'              => '%s\'s profile',
        'avatar'                  => 'Avatar',
        'avatar_title'            => 'User avatar',
        'username'                => 'Username',
        'username_title'          => 'Since you logged in using an external Authentication service, you may change your username once.',
        'email'                   => 'Email',
        'region'                  => 'Region',
        'select_region'           => 'Select region',
        'show_as_anonymous'       => 'Show as Anonymous',
        'show_as_anonymous_title' => 'Enabling this option will show you as \'Anonymous\' when viewing routes that are not part of any teams you are a part of.
                            For your own routes and for routes part of your teams, your name will always be visible.',
        'echo_color'              => 'Synchronized route edit color',
        'echo_color_title'        => 'When editing a route cooperatively with a team member, this color will uniquely identify you.',
        'save'                    => 'Save',

        'account_delete_consequences'                      => 'If you delete your Keystone.guru account the following will happen',
        'account_delete_consequence_routes'                => 'Routes',
        'account_delete_consequence_routes_delete'         => 'Your %s route(s) will be deleted.',
        'account_delete_consequence_teams'                 => 'Teams',
        'account_delete_consequence_teams_you_are_removed' => 'You will be removed from this team.',
        'account_delete_consequence_teams_new_admin'       => '%s will be appointed Admin of this team.',
        'account_delete_consequence_teams_team_deleted'    => 'This team will be deleted (you are the only user in this team).',
        'account_delete_consequence_patreon'               => 'The connection between Patreon and Keystone.guru will be terminated. You will no longer receive Patreon rewards.',
        'account_delete_consequence_reports'               => 'Reports',
        'account_delete_consequence_reports_unresolved'    => 'Your %s unresolved report(s) will be deleted',
        'account_delete_warning'                           => 'Your account will be permanently deleted. There is no turning back.',
        'account_delete_confirm'                           => 'Delete my Keystone.guru account',

        'unlink_from_patreon'         => 'Unlink from Patreon',
        'link_to_patreon_success'     => 'Your account is linked to Patreon. Thank you! Your active subscriptions are listed below. Anything missing? Let me know as soon as possible on Discord and I will look into it.',
        'link_to_patreon'             => 'Link to Patreon',
        'link_to_patreon_description' => 'In order to claim your Patreon rewards, you need to link your Patreon account',
        'patreon_benefit_table'             => [
            'header_active'  => 'Active',
            'header_benefit' => 'Benefit',
        ],

        'current_password'     => 'Current password',
        'new_password'         => 'New password',
        'new_password_confirm' => 'New password (confirm)',
        'submit'               => 'Submit',

        'ga_cookies_opt_out'  => 'Google Analytics cookies opt-out',
        'reports_description' => 'All routes, enemies and other reports you have made on the site will be listed here.',

        'reports_table_header_id'         => 'Id',
        'reports_table_header_category'   => 'Category',
        'reports_table_header_message'    => 'Message',
        'reports_table_header_created_at' => 'Created at',
        'reports_table_header_status'     => 'Status',
        'reports_table_action_handled'    => 'Handled',
    ],
    'favorites' => [
        'title' => 'My favorites',
    ],
    'overview'  => [
        'title'                    => 'Overview',
        'favorites'                => 'Favorites',
        'tags'                     => 'Tags',
        'teams'                    => 'Teams',
        'profile'                  => 'Profile',
        'route_coverage'           => 'Route coverage',
        'route_overview'           => 'Route overview',
        'welcome_text'             => 'Welcome to Keystone.guru! Get started by creating a route, or a new team to collaboratively create routes with your friends.',
        'create_route'             => 'Create route',
        'create_route_description' => 'A route is a path through a dungeon to reach the end goal of 100% enemy forces while killing all bosses.
                                Various tools help you organize your intended path for your party members. You can share them with the world - or keep them private and organize them as you wish.',
        'create_team'              => 'Create team',
        'create_team_description'  => 'A team is used to share your routes with friends and help everyone stay in-sync with the latest changes to the routes you do more often.
                                Collaborative editing make adjusting an existing route much easier while you spar for the best route for your team.',

    ],
    'routes'    => [
        'title' => 'My routes',
    ],
    'tags'      => [
        'title'                             => 'My tags',
        'header'                            => 'My tags',
        'description'                       => 'The tagging feature allows you to organize your routes the way you see fit. You can add tags to routes by viewing the Actions for each route in %s.
                You can manage tags for your own routes here. Nobody else will be able to view your tags - for routes attached to a team
                you can manage a separate set of tags for just that team by visiting the Tags section when viewing your team.',
        'link_your_personal_route_overview' => 'your personal route overview',
    ],
    'view'      => [
        'title'  => '%s\'s routes',
        'header' => '%s\'s routes',
    ],
];
