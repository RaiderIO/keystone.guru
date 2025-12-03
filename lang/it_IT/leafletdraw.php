<?php

return [

    'draw' => [
        'toolbar' => [
            'actions' => [
                'title' => 'Annulla disegno',
                'text'  => 'Annulla',
            ],
            'finish' => [
                'title' => 'Termina disegno',
                'text'  => 'Fine',
            ],
            'undo' => [
                'title' => 'Elimina l\'ultimo punto disegnato',
                'text'  => 'Elimina ultimo punto',
            ],
            'buttons' => [
                'polyline'     => 'Disegna una polilinea',
                'polygon'      => 'Disegna un poligono',
                'rectangle'    => 'Disegna un rettangolo',
                'circle'       => 'Disegna un cerchio',
                'marker'       => 'Disegna un marker',
                'circlemarker' => 'Disegna un marker circolare',
            ],
        ],
        'handlers' => [
            'circle' => [
                'tooltip' => [
                    'start' => 'Fai clic e trascina per disegnare il cerchio.',
                ],
                'radius' => 'Raggio',
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
            'polygon' => [
                'tooltip' => [
                    'start' => 'Fai clic per iniziare a disegnare la forma.',
                    'cont'  => 'Fai clic per continuare a disegnare la forma.',
                    'end'   => 'Fai clic sul primo punto per chiudere questa forma.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Errore:</strong> i bordi della forma non possono incrociarsi!',
                'tooltip' => [
                    'start' => 'Fai clic per iniziare a disegnare la linea.',
                    'cont'  => 'Fai clic per continuare a disegnare la linea.',
                    'end'   => 'Fai clic sull\'ultimo punto per terminare la linea.',
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
            'path' => [
                'tooltip' => [
                    'start' => 'Fai clic per iniziare a disegnare il percorso.',
                    'cont'  => 'Fai clic per continuare a disegnare il percorso.',
                    'end'   => 'Fai clic sul pulsante \'Fine\' sulla barra degli strumenti per completare il tuo percorso.',
                ],
            ],
            'brushline' => [
                'tooltip' => [
                    'start' => 'Fai clic per iniziare a disegnare la linea.',
                    'cont'  => 'Fai clic e trascina per continuare a disegnare la linea.',
                    'end'   => 'Continua a fare clic/trascinare, quando hai finito, premi il pulsante \'Fine\' sulla barra degli strumenti per completare la tua linea.',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar' => [
            'actions' => [
                'save' => [
                    'title' => 'Salva modifiche',
                    'text'  => 'Salva',
                ],
                'cancel' => [
                    'title' => 'Annulla modifica, scarta tutte le modifiche',
                    'text'  => 'Annulla',
                ],
                'clearAll' => [
                    'title' => 'Cancella tutti i livelli',
                    'text'  => 'Cancella Tutto',
                ],
            ],
            'buttons' => [
                'edit'           => 'Modifica livelli',
                'editDisabled'   => 'Nessun livello da modificare',
                'remove'         => 'Elimina livelli',
                'removeDisabled' => 'Nessun livello da eliminare',
            ],
        ],
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'text'    => 'Trascina maniglie o marker per modificare le caratteristiche.',
                    'subtext' => 'Fai clic su annulla per annullare le modifiche.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Fai clic su una caratteristica per rimuoverla.',
                ],
            ],
        ],
    ],

];
