<?php

return [

    'draw' => [
        'toolbar' => [
            'actions' => [
                'title' => '',
                'text'  => '',
            ],
            'finish' => [
                'title' => '',
                'text'  => '',
            ],
            'undo' => [
                'title' => '',
                'text'  => '',
            ],
            'buttons' => [
                'polyline'     => '',
                'polygon'      => '',
                'rectangle'    => '',
                'circle'       => '',
                'marker'       => '',
                'circlemarker' => '',
            ],
        ],
        'handlers' => [
            'circle' => [
                'tooltip' => [
                    'start' => '',
                ],
                'radius'  => '',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => '',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => '',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'start' => '',
                    'cont'  => '',
                    'end'   => '',
                ],
            ],
            'polyline' => [
                'error'   => '',
                'tooltip' => [
                    'start' => '',
                    'cont'  => '',
                    'end'   => '',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => '',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => '',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'start' => '',
                    'cont'  => '',
                    'end'   => '',
                ],
            ],
            'brushline' => [
                'tooltip' => [
                    'start' => '',
                    'cont'  => '',
                    'end'   => '',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar' => [
            'actions' => [
                'save' => [
                    'title' => '',
                    'text'  => '',
                ],
                'cancel' => [
                    'title' => '',
                    'text'  => '',
                ],
                'clearAll' => [
                    'title' => '',
                    'text'  => '',
                ],
            ],
            'buttons' => [
                'edit'           => '',
                'editDisabled'   => '',
                'remove'         => '',
                'removeDisabled' => '',
            ],
        ],
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'text'    => '',
                    'subtext' => '',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => '',
                ],
            ],
        ],
    ],

];
