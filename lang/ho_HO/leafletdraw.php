<?php

return [
    // format: {
    //  numeric: {
    //      delimiters: {
    //          thousands: ',',
    //          decimal: '.'
    //      }
    //  }
    // },
    'draw' => [
        'toolbar' => [
            // #TODO: this should be reorganized where actions are nested in actions
            // ex: actions.undo  or actions.cancel
            'actions' => [
                'title' => 'Hodor',
                'text'  => 'Hodor',
            ],
            'finish' => [
                'title' => 'Hodor',
                'text'  => 'Hodor',
            ],
            'undo' => [
                'title' => 'Hodor',
                'text'  => 'Hodor',
            ],
            'buttons' => [
                'polyline'     => 'Hodor',
                'polygon'      => 'Hodor',
                'rectangle'    => 'Hodor',
                'circle'       => 'Hodor',
                'marker'       => 'Hodor',
                'circlemarker' => 'Hodor',
            ],
        ],
        'handlers' => [
            'circle' => [
                'tooltip' => [
                    'start' => 'Hodor',
                ],
                'radius' => 'Hodor',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Hodor',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => 'Hodor',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'start' => 'Hodor',
                    'cont'  => 'Hodor',
                    'end'   => 'Hodor',
                ],
            ],
            'polyline' => [
                'error'   => 'Hodor',
                'tooltip' => [
                    'start' => 'Hodor',
                    'cont'  => 'Hodor',
                    'end'   => 'Hodor',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => 'Hodor',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => 'Hodor',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'start' => 'Hodor',
                    'cont'  => 'Hodor',
                    'end'   => 'Hodor',
                ],
            ],
            'brushline' => [
                'tooltip' => [
                    'start' => 'Hodor',
                    'cont'  => 'Hodor',
                    'end'   => 'Hodor',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar' => [
            'actions' => [
                'save' => [
                    'title' => 'Hodor',
                    'text'  => 'Hodor',
                ],
                'cancel' => [
                    'title' => 'Hodor',
                    'text'  => 'Hodor',
                ],
                'clearAll' => [
                    'title' => 'Hodor',
                    'text'  => 'Hodor',
                ],
            ],
            'buttons' => [
                'edit'           => 'Hodor',
                'editDisabled'   => 'Hodor',
                'remove'         => 'Hodor',
                'removeDisabled' => 'Hodor',
            ],
        ],
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'text'    => 'Hodor',
                    'subtext' => 'Hodor',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Hodor',
                ],
            ],
        ],
    ],
];
