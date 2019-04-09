<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Javascript Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the Javascript of this application.
    |
    */

    // Handlebars
    'npc_name_label' => 'Name',
    'enemy_forces_label' => 'Enemy forces',
    'enemy_display_type_label' => 'Enemy display type',
    'mdt_enemy_mapping_label' => 'MDT enemy mapping',
    'base_health_label' => 'Base health',
    'no_npc_found_label' => 'No NPC found for this enemy',

    'teeming_label' => 'Teeming',

    'admin_only_label' => 'Admin only',
    'id_label' => 'ID',
    'faction_label' => 'Faction',
    'npc_id_label' => 'NPC_ID',
    'attached_to_pack_label' => 'Pack',
    'is_mdt_label' => 'MDT',
    'mdt_id_label' => 'MDT_ID',
    'enemy_id_label' => 'ENEMY_ID',
    'visual_label' => 'Visual',

    'color_label' => 'Color',

    'clone_label' => 'Clone',
    'delete_label' => 'Delete',

    'selected_label' => 'selected',

    'affixes_label' => 'Affixes',
    'attributes_label' => 'Attributes',
    'setup_label' => 'Setup',

    'submit_label' => 'Submit',

    // Route edit tools
    'path' => 'Path',
    'killzone' => 'Killzone',
    'mapcomment' => 'Comment',
    'brushline' => 'Draw',
    'brushline_title' => 'Draw a line',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'finish' => 'Finish',
    'finish_drawing' => 'Finish drawing',

    'enemypack' => 'Pack',
    'enemy' => 'Enemy',
    'enemypatrol' => 'Patrol',
    'dungeonstartmarker' => 'Start',
    'dungeonfloorswitchmarker' => 'Floor',

    // Raid markers
    'title_raid_marker_no_selection' => 'No raid marker',
    'title_raid_marker_star' => 'Star',
    'title_raid_marker_circle' => 'Circle',
    'title_raid_marker_diamond' => 'Diamond',
    'title_raid_marker_triangle' => 'Triangle',
    'title_raid_marker_moon' => 'Moon',
    'title_raid_marker_square' => 'Square',
    'title_raid_marker_cross' => 'Cross',
    'title_raid_marker_skull' => 'Skull',

    // Visualisation
    'aggressiveness_label' => 'Aggressiveness',


    // Admin
    'object.deleted' => 'Objects deleted successfully.',

    // Home page
    'warnings_label' => 'Warnings',
    'category_label' => 'Category',
    'message_label' => 'Message',

    // Dungeonroute edit
    'settings_saved' => 'Settings saved successfully',
    'route_published' => 'Route published',
    'route_unpublished' => 'Route unpublished',

    // Dungeonroute table
    'vote' => 'vote',
    'votes' => 'votes',
    'route_delete_confirm' => 'Are you sure you wish to delete this route?',
    'route_delete_successful' => 'Route deleted successfully',

    // Map
    'intro_1' => 'Welcome to Keystone.guru! To begin, this is the sidebar. Here you can adjust options for your route or view information about it.',
    'intro_2' => 'You can use this button to hide or show the sidebar.',

    'intro_3' => 'Here you can select different visualization options.',
    'intro_4' => 'You can choose from multiple different visualizations to help you quickly find the information you need.',

    'intro_5' => 'If your dungeon has multiple floors, this is where you can change floors. You can also click the doors on the map to go to the next floor.',

    'intro_6' => 'These are some actions you can perform on the current route, register if you\'re not registered or login if you have.',

    'intro_7' => 'These are your route manipulation tools.',
    'intro_8' => 'This label indicates the current progress with enemy forces. Use \'killzones\' to mark an enemy as killed and see this label updated (more on this in a bit!).',
    'intro_9' => 'You can draw paths with this tool. Click it, then draw a path (which is a simple line with directional arrows) from A to B, with as many points are you like. Once finished, you can click
            the line on the map to change its color. You can add as many paths as you want, use the colors to your advantage. Color the line yellow for Rogue Shrouding,
            or purple for a Warlock Gateway, for example.',
    'intro_10' => 'This is a \'killzone\'. You use these zones to indicate what enemies you are killing, and most importantly, where. Place a zone on the map and click it again.
            You can then select any enemy on the map that has not already \'been killed\' by another kill zone. When you select a pack, you automatically select all enemies in the pack.
            Once you have selected enemies your enemy forces (the label above here) will update to reflect your new enemy forces counter.',
    'intro_11' => 'Use this control to place comments on the map, for example to indicate you\'re skipping a patrol or to indicate details and background info in your route.',
    'intro_12' => 'Use this control to free draw lines on your route.',

    'intro_13' => 'This is the edit button. You can use it to adjust your created routes, move your killzones, comments or free drawn lines.',
    'intro_14' => 'This is the delete button. Click it once, then select the controls you wish to delete. Deleting happens in a preview mode, you have to confirm your delete in a label
            that pops up once you press the button. You can then confirm or cancel your staged changes. If you confirm the deletion, there is no turning back!',

    'intro_15' => 'The color selection affect newly placed free drawn lines and routes. Killzones get the selected color by default.',
    'intro_16' => 'The weight (thickness) of newly placed free drawn lines and routes.',

    'intro_17' => 'These are your visibility toggles. You can hide enemies, enemy patrols, enemy packs, your own routes, your own killzones, all map comments, start markers and floor switch markers.',

    // Sidebar
    'sidebar_expand' => 'Expand the sidebar',
    'sidebar_collapse' => 'Collapse the sidebar',
    'copied_to_clipboard' => 'Copied to clipboard',


    // General site modals etc
    'min_password_length' => 'Minimum password length is 8',
    'weak' => 'Weak',
    'medium' => 'Medium',
    'strong' => 'Strong',
    'contains_username' => 'Password cannot contain your username',

    // MDT Modal
    'mdt_faction' => 'Faction',
    'mdt_dungeon' => 'Dungeon',
    'mdt_affixes' => 'Affixes',
    'mdt_pulls' => 'Pulls',
    'mdt_drawn_lines' => 'Drawn lines',
    'mdt_notes' => 'Notes',
    'mdt_enemy_forces' => 'Enemy forces',

    // Default AJAX failed messages
    'ajax_error_default' => 'An error occurred while performing your request. Please try again.',
    'ajax_error_403' => 'You are not authorized to perform this request.',
    'ajax_error_404' => 'The requested resource was not found.',
    'ajax_error_419' => 'Your session has expired. Refresh the page.',

    // Map comment popup
    'map_comment_label' => 'Comment'
];
