<?php

use App\Models\Laratrust\Role;

return [
    'create_users'    => true,
    'roles_structure' => [
        Role::ROLE_ADMIN => [
            'dungeons'     => 'c,r,u,d',
            'expansions'   => 'c,r,u,d',
            'npcs'         => 'c,r,u,d',
            'profile'      => 'r,u',
            'dungeonroute' => 'c,r,u,d',
        ],
        Role::ROLE_INTERNAL_TEAM => [
            'profile'      => 'r,u',
            'dungeonroute' => 'c,r,u,d',
        ],
        // Same permissions as internal_team - the extra reach of this role is route-level (api_role:admin|ai_agent)
        Role::ROLE_AI_AGENT => [
            'profile'      => 'r,u',
            'dungeonroute' => 'c,r,u,d',
        ],
        Role::ROLE_USER => [
            'profile'      => 'r,u',
            'dungeonroute' => 'c,r,u,d',
        ],
    ],
    'permission_structure' => [
        'cru_user' => [
            'profile' => 'c,r,u',
        ],
    ],
    'permissions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete',
    ],
];
