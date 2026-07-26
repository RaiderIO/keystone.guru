<?php

return [
    'published_state' => [
        'unpublished'     => 'Only me',
        'team'            => 'My team',
        'world_with_link' => 'Everyone with the link',
        'world'           => 'Everyone',
    ],
    'index' => [
        'title'                   => 'My collections',
        'header'                  => 'My collections',
        'description'             => 'A collection is a shareable list of your routes, for example all your routes for this week.',
        'create_collection'       => 'New collection',
        'no_collections'          => 'You have not created any collections yet.',
        'table_header_name'       => 'Name',
        'table_header_visibility' => 'Visible to',
        'table_header_routes'     => 'Routes',
        'view'                    => 'View',
    ],
    'new' => [
        'title'  => 'New collection',
        'header' => 'New collection',
    ],
    'edit' => [
        'title'           => 'Edit %s',
        'view_collection' => 'View collection',
    ],
    'view' => [
        'title'       => '%s',
        'by_author'   => 'A collection by :author',
        'route_count' => '{0} No routes|{1} :count route|[2,*] :count routes',
        'no_routes'   => 'This collection does not contain any routes that you may view.',
    ],
    'details' => [
        'name'                 => 'Name',
        'description'          => 'Description',
        'published_state'      => 'Visible to',
        'published_state_help' => 'Sharing a collection never publishes the routes inside it - a route that is not published stays hidden.',
        'team'                 => 'Team',
        'team_none'            => 'No team',
        'team_help'            => 'The team to share this collection with, when the collection is visible to your team.',
        'dungeon_routes'       => 'Routes',
        'dungeon_routes_none'  => 'You have not created any routes yet.',
        'dungeon_routes_help'  => 'Hold ctrl (or cmd) to select multiple routes, up to a maximum of :max. They are shown in the order you selected them in.',
        'save'                 => 'Save',
        'submit'               => 'Create collection',
        'delete'               => 'Delete collection',
    ],
];
