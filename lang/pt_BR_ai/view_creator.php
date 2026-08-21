<?php

return [
    'directory' => [
        'title'              => 'Criadores de rotas',
        'header'             => 'Criadores de rotas',
        'description'        => 'Navegue pelas pessoas que criam rotas no Keystone.guru. Criadores com rotas publicadas são listados automaticamente - abra um perfil para ver suas rotas fixadas e onde mais encontrá-los.',
        'search_label'       => 'Buscar um criador',
        'search_placeholder' => 'Buscar por nome',
        'search_submit'      => 'Buscar',
        'empty'              => 'Ainda não há criadores listados.',
        'empty_for_search'   => 'Nenhum criador encontrado correspondendo a ":search".',
    ],
    'featured' => [
        'title'   => 'Criadores em destaque',
        'see_all' => 'Ver todos os criadores',
        /** Tooltip on a rail entry - it carries the name because the name itself may be clipped to an ellipsis */
        'entry_title' => ':name - :routes',
    ],
    'card' => [
        'route_count' => '{0} Nenhuma rota publicada|{1} :count rota publicada|[2,*] :count rotas publicadas',
    ],
];
