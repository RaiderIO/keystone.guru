<?php

return [
    'draw' => [
        'handlers' => [
            'brushline' => [
                'tooltip' => [
                    'cont'  => 'Clique e arraste para continuar desenhando a linha.',
                    'end'   => 'Continue clicando/arrastando, quando terminar, pressione o botão \'Concluir\' na barra de ferramentas para completar sua linha.',
                    'start' => 'Clique para começar a desenhar a linha.',
                ],
            ],
            'circle' => [
                'radius'  => 'Raio',
                'tooltip' => [
                    'start' => 'Clique e arraste para desenhar um círculo.',
                ],
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Clique no mapa para posicionar o marcador de círculo.',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => 'Clique no mapa para posicionar o marcador.',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'cont'  => 'Clique para continuar desenhando o caminho.',
                    'end'   => 'Clique no botão \'Concluir\' na barra de ferramentas para completar seu caminho.',
                    'start' => 'Clique para começar a desenhar o caminho.',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'cont'  => 'Clique para continuar desenhando a forma.',
                    'end'   => 'Clique no primeiro ponto para fechar esta forma.',
                    'start' => 'Clique para começar a desenhar a forma.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Erro:</strong> as arestas da forma não podem se cruzar!',
                'tooltip' => [
                    'cont'  => 'Clique para continuar desenhando a linha.',
                    'end'   => 'Clique no último ponto para finalizar a linha.',
                    'start' => 'Clique para começar a desenhar a linha.',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => 'Clique e arraste para desenhar um retângulo.',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => 'Solte o mouse para terminar de desenhar.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'text'  => 'Cancelar',
                'title' => 'Cancelar desenho',
            ],
            'buttons' => [
                'circle'       => 'Desenhar um círculo',
                'circlemarker' => 'Desenhar um marcador de círculo',
                'marker'       => 'Desenhar um marcador',
                'polygon'      => 'Desenhar um polígono',
                'polyline'     => 'Desenhar uma polilinha',
                'rectangle'    => 'Desenhar um retângulo',
            ],
            'finish' => [
                'text'  => 'Concluir',
                'title' => 'Concluir desenho',
            ],
            'undo' => [
                'text'  => 'Excluir último ponto',
                'title' => 'Excluir último ponto desenhado',
            ],
        ],
    ],
    'edit' => [
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'subtext' => 'Clique em cancelar para desfazer as alterações.',
                    'text'    => 'Arraste as alças ou marcadores para editar as funcionalidades.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Clique em uma funcionalidade para remover.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'cancel' => [
                    'text'  => 'Cancelar',
                    'title' => 'Cancelar edição, descarta todas as alterações',
                ],
                'clearAll' => [
                    'text'  => 'Limpar Tudo',
                    'title' => 'Limpar todas as camadas',
                ],
                'save' => [
                    'text'  => 'Salvar',
                    'title' => 'Salvar alterações',
                ],
            ],
            'buttons' => [
                'edit'           => 'Editar camadas',
                'editDisabled'   => 'Nenhuma camada para editar',
                'remove'         => 'Excluir camadas',
                'removeDisabled' => 'Nenhuma camada para excluir',
            ],
        ],
    ],
];
