<?php

return [
    'error' => [
        'unauthenticated'        => 'Not authenticated. Connect with an OAuth access token.',
        'feature_inactive'       => 'The keystone.guru MCP server is not available for your account.',
        'missing_scope'          => 'Your access token lacks the :scope scope this tool needs. Reconnect and grant it.',
        'rate_limited'           => 'Too many requests for this tool. Try again in :seconds seconds.',
        'request_refused'        => 'The request was refused.',
        'dungeon_not_found'      => 'No active dungeon matches ":dungeon". Use list_dungeons for the valid keys.',
        'route_not_found'        => 'You have no route with key ":route". Use list_my_routes for your routes.',
        'pull_not_found'         => 'The route has no pull :pull.',
        'unable_to_save_route'   => 'The route could not be saved.',
        'unable_to_save_pull'    => 'The pull could not be saved.',
        'unable_to_delete_pull'  => 'The pull could not be removed.',
        'no_enemies_given'       => 'Give at least one pack id or enemy id.',
        'unknown_pack_ids'       => 'These pack ids are not part of the route\'s dungeon mapping: :ids.',
        'unknown_enemy_ids'      => 'These enemy ids are not part of the route\'s dungeon mapping: :ids.',
        'enemies_already_pulled' => 'These enemies are already in another pull (enemy id => pull number): :enemies. Remove them from that pull first, or leave them out.',
        'reorder_mismatch'       => 'The order must list every pull id of the route exactly once. Route pull ids: :ids.',
    ],
];
