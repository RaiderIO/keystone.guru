<?php

return [

    'view_route_not_published'                            => 'This route is not published and cannot be viewed. Please ask the author to publish this route to view it.',
    'present_route_not_published'                         => 'This route is not published and cannot be presented. Please ask the author to publish this route to present it.',
    'embed_route_not_published'                           => 'This route is not published and cannot be viewed. Please ask the author to publish this route to view it.',
    'embed_route_sandbox_not_allowed'                     => 'Temporary routes cannot be embedded.',
    'publish_not_all_required_enemies_killed'             => 'Unable to change sharing settings: not all required enemies have been killed.',
    'publish_route_is_upgrade_draft'                      => 'An upgrade draft cannot be shared on its own. Apply it to the route it upgrades to publish your changes.',
    'apply_upgrade_route_not_upgrade_draft'               => 'This route is not an upgrade draft, so there is nothing to apply.',
    'apply_upgrade_original_route_deleted'                => 'The route this draft upgrades no longer exists.',
    'apply_upgrade_draft_not_all_required_enemies_killed' => 'Unable to apply this upgrade draft: the new mapping version requires enemies that have not been killed in the draft. Kill the missing enemies before applying, or discard the draft.',
    'discard_upgrade_route_not_upgrade_draft'             => 'This route is not an upgrade draft, so there is nothing to discard.',
    'add_kill_zone_limit_reached'                         => 'Unable to add more than :limit pulls to a single route.',
    'add_brushline_limit_reached'                         => 'Unable to add more than :limit free-drawn lines to a single route.',
    'add_path_limit_reached'                              => 'Unable to add more than :limit paths to a single route.',
    'add_arrow_limit_reached'                             => 'Unable to add more than :limit arrows to a single route.',
    'add_map_icon_limit_reached'                          => 'Unable to add more than :limit map icons to a single route.',
    'schedule_publish_route_not_in_team'                  => 'Unable to schedule publish: this route is not assigned to a team.',
    'game_version_not_active'                             => 'This game version is no longer active.',
    'expansion_not_active'                                => 'This expansion is no longer active.',
    'dungeon_not_active'                                  => 'This dungeon is no longer active.',
    'season_not_active'                                   => 'This season could not be found or is no longer active.',

    'claim_route_not_claimable'                   => 'This route already has an author and cannot be claimed.',
    'make_role_only_super_admins_may_grant_admin' => 'Only super admins may grant or revoke the admin role.',
    'create_global_map_icon_admin_only'           => 'Only administrators may create map icons that are not attached to a route.',
    'update_map_icon_admin_only'                  => 'Only administrators may change map icons that are not attached to a route or team.',
    'delete_map_icon_admin_only'                  => 'Only administrators may delete map icons that are not attached to a route.',

];
