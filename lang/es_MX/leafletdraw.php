<?php

return [
    'draw' => [
        'handlers' => [
            'brushline' => [
                'tooltip' => [
                    'cont'  => 'Haz clic y arrastra para continuar dibujando la línea.',
                    'end'   => 'Continúa haciendo clic/arrastrando, cuando termines, presiona el botón \'Terminar\' en la barra de herramientas para completar tu línea.',
                    'start' => 'Haz clic para empezar a dibujar la línea.',
                ],
            ],
            'circle' => [
                'radius'  => 'Radio',
                'tooltip' => [
                    'start' => 'Haz clic y arrastra para dibujar un círculo.',
                ],
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Haz clic en el mapa para colocar el marcador de círculo.',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => 'Haz clic en el mapa para colocar el marcador.',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'cont'  => 'Haz clic para continuar dibujando la ruta.',
                    'end'   => 'Haz clic en el botón \'Terminar\' en la barra de herramientas para completar tu ruta.',
                    'start' => 'Haz clic para comenzar a dibujar la ruta.',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'cont'  => 'Haz clic para continuar dibujando la forma.',
                    'end'   => 'Haz clic en el primer punto para cerrar esta forma.',
                    'start' => 'Haz clic para comenzar a dibujar la forma.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Error:</strong> ¡los bordes de la forma no pueden cruzarse!',
                'tooltip' => [
                    'cont'  => 'Haz clic para continuar dibujando la línea.',
                    'end'   => 'Haz clic en el último punto para terminar la línea.',
                    'start' => 'Haz clic para comenzar a dibujar la línea.',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => 'Haz clic y arrastra para dibujar un rectángulo.',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => 'Suelta el mouse para terminar de dibujar.',
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
                'text'  => 'Eliminar último punto',
                'title' => 'Eliminar último punto dibujado',
            ],
        ],
    ],
    'edit' => [
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'subtext' => 'Haz clic en cancelar para deshacer los cambios.',
                    'text'    => 'Arrastra los manejadores o marcadores para editar las características.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Haz clic en una característica para eliminarla.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'cancel' => [
                    'text'  => 'Cancelar',
                    'title' => 'Cancelar edición, descarta todos los cambios',
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
