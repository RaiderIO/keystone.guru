<?php

return [

    'draw' => [
        'toolbar' => [
            'actions' => [
                'title' => 'Cancelar dibujo',
                'text'  => 'Cancelar',
            ],
            'finish' => [
                'title' => 'Terminar dibujo',
                'text'  => 'Terminar',
            ],
            'undo' => [
                'title' => 'Eliminar último punto dibujado',
                'text'  => 'Eliminar último punto',
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
            'circle' => [
                'tooltip' => [
                    'start' => 'Haz clic y arrastra para dibujar un círculo.',
                ],
                'radius' => 'Radio',
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
            'polygon' => [
                'tooltip' => [
                    'start' => 'Haz clic para comenzar a dibujar la forma.',
                    'cont'  => 'Haz clic para continuar dibujando la forma.',
                    'end'   => 'Haz clic en el primer punto para cerrar esta forma.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Error:</strong> ¡los bordes de la forma no pueden cruzarse!',
                'tooltip' => [
                    'start' => 'Haz clic para comenzar a dibujar la línea.',
                    'cont'  => 'Haz clic para continuar dibujando la línea.',
                    'end'   => 'Haz clic en el último punto para terminar la línea.',
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
            'path' => [
                'tooltip' => [
                    'start' => 'Haz clic para comenzar a dibujar la ruta.',
                    'cont'  => 'Haz clic para continuar dibujando la ruta.',
                    'end'   => 'Haz clic en el botón \'Terminar\' en la barra de herramientas para completar tu ruta.',
                ],
            ],
            'brushline' => [
                'tooltip' => [
                    'start' => 'Haz clic para empezar a dibujar la línea.',
                    'cont'  => 'Haz clic y arrastra para continuar dibujando la línea.',
                    'end'   => 'Continúa haciendo clic/arrastrando, cuando termines, presiona el botón \'Terminar\' en la barra de herramientas para completar tu línea.',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar' => [
            'actions' => [
                'save' => [
                    'title' => 'Guardar cambios',
                    'text'  => 'Guardar',
                ],
                'cancel' => [
                    'title' => 'Cancelar edición, descarta todos los cambios',
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
            'edit' => [
                'tooltip' => [
                    'text'    => 'Arrastra los manejadores o marcadores para editar las características.',
                    'subtext' => 'Haz clic en cancelar para deshacer los cambios.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Haz clic en una característica para eliminarla.',
                ],
            ],
        ],
    ],

];
