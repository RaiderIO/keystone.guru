<?php

return [
    'create_users' => true,
    'roles_structure' => [
        'admin' => [
            'dungeons' => 'c,r,u,d',
            'expansions' => 'c,r,u,d',
            'npcs' => 'c,r,u,d',
            'profile' => 'r,u',
            'dungeonroute' => 'c,r,u,d',
        ],
        'user' => [
            'profile' => 'r,u',
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
