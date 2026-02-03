<?php

return [

    'draw' => [
        'toolbar'  => [
            'actions' => [
                'title' => 'Cancelar dibujo',
                'text'  => 'Cancelar',
            ],
            'finish'  => [
                'title' => 'Terminar dibujo',
                'text'  => 'Terminar',
            ],
            'undo'    => [
                'title' => 'Eliminar el último punto dibujado',
                'text'  => 'Eliminar el último punto',
            ],
            'buttons' => [
                'polyline'     => 'Dibujar una polilínea',
                'polygon'      => 'Dibujar un polígono',
                'rectangle'    => 'Dibujar un rectángulo',
                'circle'       => 'Dibujar un círculo',
                'marker'       => 'Dibujar un marcador',
                'circlemarker' => 'Dibujar un marcador de círculo',
            ],
        ],
        'handlers' => [
            'circle'       => [
                'tooltip' => [
                    'start' => 'Haga clic y arrastre para dibujar un círculo.',
                ],
                'radius'  => 'Radio',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Haga clic en el mapa para colocar un marcador de círculo.',
                ],
            ],
            'marker'       => [
                'tooltip' => [
                    'start' => 'Haga clic en el mapa para colocar un marcador.',
                ],
            ],
            'polygon'      => [
                'tooltip' => [
                    'start' => 'Haga clic para comenzar a dibujar la forma.',
                    'cont'  => 'Haga clic para continuar dibujando la forma.',
                    'end'   => 'Haga clic en el primer punto para cerrar esta forma.',
                ],
            ],
            'polyline'     => [
                'error'   => '<strong>Error:</strong> ¡los bordes de la forma no pueden cruzarse!',
                'tooltip' => [
                    'start' => 'Haga clic para comenzar a dibujar la línea.',
                    'cont'  => 'Haga clic para continuar dibujando la línea.',
                    'end'   => 'Haga clic en el último punto para terminar la línea.',
                ],
            ],
            'rectangle'    => [
                'tooltip' => [
                    'start' => 'Haga clic y arrastre para dibujar un rectángulo.',
                ],
            ],
            'simpleshape'  => [
                'tooltip' => [
                    'end' => 'Suelte el mouse para terminar de dibujar.',
                ],
            ],
            'path'         => [
                'tooltip' => [
                    'start' => 'Haga clic para comenzar a dibujar el camino.',
                    'cont'  => 'Haga clic para continuar dibujando el camino.',
                    'end'   => 'Haga clic en el botón \'Terminar\' en la barra de herramientas para completar su camino.',
                ],
            ],
            'brushline'    => [
                'tooltip' => [
                    'start' => 'Haga clic para comenzar a dibujar la línea.',
                    'cont'  => 'Haga clic y arrastre para continuar dibujando la línea.',
                    'end'   => 'Continúe haciendo clic/arrastrando, cuando termine, presione el botón \'Terminar\' en la barra de herramientas para completar su línea.',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar'  => [
            'actions' => [
                'save'     => [
                    'title' => 'Guardar cambios',
                    'text'  => 'Guardar',
                ],
                'cancel'   => [
                    'title' => 'Cancelar la edición, descarta todos los cambios',
                    'text'  => 'Cancelar',
                ],
                'clearAll' => [
                    'title' => 'Borrar todas las capas',
                    'text'  => 'Borrar todo',
                ],
            ],
            'buttons' => [
                'edit'           => 'Editar capas',
                'editDisabled'   => 'No hay capas para editar',
                'remove'         => 'Eliminar capas',
                'removeDisabled' => 'No hay capas para eliminar',
            ],
        ],
        'handlers' => [
            'edit'   => [
                'tooltip' => [
                    'text'    => 'Arrastre los controladores o marcadores para editar las características.',
                    'subtext' => 'Haga clic en cancelar para deshacer los cambios.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Haga clic en una característica para eliminarla.',
                ],
            ],
        ],
    ],

];
