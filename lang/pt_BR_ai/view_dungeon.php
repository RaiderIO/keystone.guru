<?php

return [
    'dungeonroute' => [
        'search' => [
            'gameversion' => [
                'dungeon' => [
                    'title'                                  => 'Pesquisar rotas para :dungeon',
                    'description'                            => 'Procure rotas por título, nível de chave, afixos, forças inimigas, classificação e mais. Selecione uma masmorra para começar.',
                    'linkpreview_default_description_search' => 'Encontre rotas M+ para :dungeon',
                    'linkpreview_title'                      => ':title | Keystone.guru',
                ],
            ],
            'list' => [
                'title'       => 'Pesquisar rotas',
                'header'      => 'Pesquisar rotas',
                'description' => 'Procure rotas por título, nível de chave, afixos, forças inimigas, classificação e mais. Selecione uma masmorra para começar.',
            ],
        ],
    ],
    'explore' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Explorar',
                'header'            => 'Explorar masmorra',
                'description'       => 'Explorar uma masmorra permite que você veja o layout da masmorra e os inimigos presentes. Ideal para simplesmente visualizar a masmorra sem criar uma rota.',
                'heatmap_available' => 'Mapa de calor disponível para masmorra',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Qualquer',
                'select_floor'            => 'Selecionar andar',
                'view_heatmap_fullscreen' => 'Ver em tela cheia',
            ],
            'view' => [
                'title' => 'Explorar :dungeon',
            ],
        ],
    ],
    'heatmap' => [
        'gameversion' => [
            'list' => [
                'title'             => 'Mapas de calor',
                'header'            => 'Mapas de calor de masmorras',
                'raider_io'         => 'Raider.IO',
                'description'       => 'Desenvolvido por :raiderIO, mapas de calor podem mostrar informações inestimáveis sobre quais inimigos são mortos por jogadores, onde eles estão se matando ou lançando certos feitiços. Filtros para nível de chave, nível de item, composição da equipe e muitos outros permitem que você foque nos dados relevantes para suas necessidades.',
                'heatmap_available' => 'Mapa de calor disponível para masmorra',
            ],
            'embed' => [
                'title'                   => ':dungeon',
                'any'                     => 'Qualquer',
                'select_floor'            => 'Selecionar andar',
                'view_heatmap_fullscreen' => 'Ver em tela cheia',
            ],
        ],
    ],

];
