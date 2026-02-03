<?php

return [

    'draw' => [
        'toolbar'  => [
            'actions' => [
                'title' => 'Cancelar desenho',
                'text'  => 'Cancelar',
            ],
            'finish'  => [
                'title' => 'Concluir desenho',
                'text'  => 'Concluir',
            ],
            'undo'    => [
                'title' => 'Excluir último ponto desenhado',
                'text'  => 'Excluir último ponto',
            ],
            'buttons' => [
                'polyline'     => 'Desenhar uma polilinha',
                'polygon'      => 'Desenhar um polígono',
                'rectangle'    => 'Desenhar um retângulo',
                'circle'       => 'Desenhar um círculo',
                'marker'       => 'Desenhar um marcador',
                'circlemarker' => 'Desenhar um marcador de círculo',
            ],
        ],
        'handlers' => [
            'circle'       => [
                'tooltip' => [
                    'start' => 'Clique e arraste para desenhar um círculo.',
                ],
                'radius'  => 'Raio',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Clique no mapa para posicionar o marcador de círculo.',
                ],
            ],
            'marker'       => [
                'tooltip' => [
                    'start' => 'Clique no mapa para posicionar o marcador.',
                ],
            ],
            'polygon'      => [
                'tooltip' => [
                    'start' => 'Clique para começar a desenhar a forma.',
                    'cont'  => 'Clique para continuar desenhando a forma.',
                    'end'   => 'Clique no primeiro ponto para fechar esta forma.',
                ],
            ],
            'polyline'     => [
                'error'   => '<strong>Erro:</strong> as arestas da forma não podem se cruzar!',
                'tooltip' => [
                    'start' => 'Clique para começar a desenhar a linha.',
                    'cont'  => 'Clique para continuar desenhando a linha.',
                    'end'   => 'Clique no último ponto para finalizar a linha.',
                ],
            ],
            'rectangle'    => [
                'tooltip' => [
                    'start' => 'Clique e arraste para desenhar um retângulo.',
                ],
            ],
            'simpleshape'  => [
                'tooltip' => [
                    'end' => 'Solte o mouse para terminar de desenhar.',
                ],
            ],
            'path'         => [
                'tooltip' => [
                    'start' => 'Clique para começar a desenhar o caminho.',
                    'cont'  => 'Clique para continuar desenhando o caminho.',
                    'end'   => 'Clique no botão \'Concluir\' na barra de ferramentas para completar seu caminho.',
                ],
            ],
            'brushline'    => [
                'tooltip' => [
                    'start' => 'Clique para começar a desenhar a linha.',
                    'cont'  => 'Clique e arraste para continuar desenhando a linha.',
                    'end'   => 'Continue clicando/arrastando, quando terminar, pressione o botão \'Concluir\' na barra de ferramentas para completar sua linha.',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar'  => [
            'actions' => [
                'save'     => [
                    'title' => 'Salvar alterações',
                    'text'  => 'Salvar',
                ],
                'cancel'   => [
                    'title' => 'Cancelar edição, descarta todas as alterações',
                    'text'  => 'Cancelar',
                ],
                'clearAll' => [
                    'title' => 'Limpar todas as camadas',
                    'text'  => 'Limpar Tudo',
                ],
            ],
            'buttons' => [
                'edit'           => 'Editar camadas',
                'editDisabled'   => 'Nenhuma camada para editar',
                'remove'         => 'Excluir camadas',
                'removeDisabled' => 'Nenhuma camada para excluir',
            ],
        ],
        'handlers' => [
            'edit'   => [
                'tooltip' => [
                    'text'    => 'Arraste as alças ou marcadores para editar as funcionalidades.',
                    'subtext' => 'Clique em cancelar para desfazer as alterações.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Clique em uma funcionalidade para remover.',
                ],
            ],
        ],
    ],

];
