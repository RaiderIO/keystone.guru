<?php

return [
    'draw' => [
        'handlers' => [
            'brushline' => [
                'tooltip' => [
                    'cont'  => 'Fai clic e trascina per continuare a disegnare la linea.',
                    'end'   => 'Continua a fare clic/trascinare, quando hai finito, premi il pulsante \'Fine\' sulla barra degli strumenti per completare la tua linea.',
                    'start' => 'Fai clic per iniziare a disegnare la linea.',
                ],
            ],
            'circle' => [
                'radius'  => 'Raggio',
                'tooltip' => [
                    'start' => 'Fai clic e trascina per disegnare il cerchio.',
                ],
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Fai clic sulla mappa per posizionare il marker circolare.',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => 'Fai clic sulla mappa per posizionare il marker.',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'cont'  => 'Fai clic per continuare a disegnare il percorso.',
                    'end'   => 'Fai clic sul pulsante \'Fine\' sulla barra degli strumenti per completare il tuo percorso.',
                    'start' => 'Fai clic per iniziare a disegnare il percorso.',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'cont'  => 'Fai clic per continuare a disegnare la forma.',
                    'end'   => 'Fai clic sul primo punto per chiudere questa forma.',
                    'start' => 'Fai clic per iniziare a disegnare la forma.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Errore:</strong> i bordi della forma non possono incrociarsi!',
                'tooltip' => [
                    'cont'  => 'Fai clic per continuare a disegnare la linea.',
                    'end'   => 'Fai clic sull\'ultimo punto per terminare la linea.',
                    'start' => 'Fai clic per iniziare a disegnare la linea.',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => 'Fai clic e trascina per disegnare il rettangolo.',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => 'Rilascia il mouse per terminare il disegno.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'text'  => 'Annulla',
                'title' => 'Annulla disegno',
            ],
            'buttons' => [
                'circle'       => 'Disegna un cerchio',
                'circlemarker' => 'Disegna un marker circolare',
                'marker'       => 'Disegna un marker',
                'polygon'      => 'Disegna un poligono',
                'polyline'     => 'Disegna una polilinea',
                'rectangle'    => 'Disegna un rettangolo',
            ],
            'finish' => [
                'text'  => 'Fine',
                'title' => 'Termina disegno',
            ],
            'undo' => [
                'text'  => 'Elimina ultimo punto',
                'title' => 'Elimina l\'ultimo punto disegnato',
            ],
        ],
    ],
    'edit' => [
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'subtext' => 'Fai clic su annulla per annullare le modifiche.',
                    'text'    => 'Trascina maniglie o marker per modificare le caratteristiche.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Fai clic su una caratteristica per rimuoverla.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'cancel' => [
                    'text'  => 'Annulla',
                    'title' => 'Annulla modifica, scarta tutte le modifiche',
                ],
                'clearAll' => [
                    'text'  => 'Cancella Tutto',
                    'title' => 'Cancella tutti i livelli',
                ],
                'save' => [
                    'text'  => 'Salva',
                    'title' => 'Salva modifiche',
                ],
            ],
            'buttons' => [
                'edit'           => 'Modifica livelli',
                'editDisabled'   => 'Nessun livello da modificare',
                'remove'         => 'Elimina livelli',
                'removeDisabled' => 'Nessun livello da eliminare',
            ],
        ],
    ],
];
