<?php

return [
    'draw' => [
        'handlers' => [
            'brushline' => [
                'tooltip' => [
                    'cont'  => 'Haga clic y arrastre para continuar dibujando la línea.',
                    'end'   => 'Continúe haciendo clic/arrastrando, cuando termine, presione el botón \'Terminar\' en la barra de herramientas para completar su línea.',
                    'start' => 'Haga clic para comenzar a dibujar la línea.',
                ],
            ],
            'circle' => [
                'radius'  => 'Radio',
                'tooltip' => [
                    'start' => 'Haga clic y arrastre para dibujar un círculo.',
                ],
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Haga clic en el mapa para colocar un marcador de círculo.',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => 'Haga clic en el mapa para colocar un marcador.',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'cont'  => 'Haga clic para continuar dibujando el camino.',
                    'end'   => 'Haga clic en el botón \'Terminar\' en la barra de herramientas para completar su camino.',
                    'start' => 'Haga clic para comenzar a dibujar el camino.',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'cont'  => 'Haga clic para continuar dibujando la forma.',
                    'end'   => 'Haga clic en el primer punto para cerrar esta forma.',
                    'start' => 'Haga clic para comenzar a dibujar la forma.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Error:</strong> ¡los bordes de la forma no pueden cruzarse!',
                'tooltip' => [
                    'cont'  => 'Haga clic para continuar dibujando la línea.',
                    'end'   => 'Haga clic en el último punto para terminar la línea.',
                    'start' => 'Haga clic para comenzar a dibujar la línea.',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => 'Haga clic y arrastre para dibujar un rectángulo.',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => 'Suelte el mouse para terminar de dibujar.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'text'  => 'Cancelar',
                'title' => 'Cancelar dibujo',
            ],
            'buttons' => [
                'circle'       => 'Dibujar un círculo',
                'circlemarker' => 'Dibujar un marcador de círculo',
                'marker'       => 'Dibujar un marcador',
                'polygon'      => 'Dibujar un polígono',
                'polyline'     => 'Dibujar una polilínea',
                'rectangle'    => 'Dibujar un rectángulo',
            ],
            'finish' => [
                'text'  => 'Terminar',
                'title' => 'Terminar dibujo',
            ],
            'undo' => [
                'text'  => 'Eliminar el último punto',
                'title' => 'Eliminar el último punto dibujado',
            ],
        ],
    ],
    'edit' => [
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'subtext' => 'Haga clic en cancelar para deshacer los cambios.',
                    'text'    => 'Arrastre los controladores o marcadores para editar las características.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Haga clic en una característica para eliminarla.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'cancel' => [
                    'text'  => 'Cancelar',
                    'title' => 'Cancelar la edición, descarta todos los cambios',
                ],
                'clearAll' => [
                    'text'  => 'Borrar todo',
                    'title' => 'Borrar todas las capas',
                ],
                'save' => [
                    'text'  => 'Guardar',
                    'title' => 'Guardar cambios',
                ],
            ],
            'buttons' => [
                'edit'           => 'Editar capas',
                'editDisabled'   => 'No hay capas para editar',
                'remove'         => 'Eliminar capas',
                'removeDisabled' => 'No hay capas para eliminar',
            ],
        ],
    ],
];
