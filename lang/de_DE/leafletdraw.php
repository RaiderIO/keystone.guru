<?php

return [

    'draw' => [
        'toolbar' => [
            'actions' => [
                'title' => 'Zeichnen abbrechen',
                'text'  => 'Abbrechen',
            ],
            'finish' => [
                'title' => 'Zeichnen beenden',
                'text'  => 'Fertig',
            ],
            'undo' => [
                'title' => 'Zuletzt gezeichneten Punkt löschen',
                'text'  => 'Letzten Punkt löschen',
            ],
            'buttons' => [
                'polyline'     => 'Eine Polylinie zeichnen',
                'polygon'      => 'Ein Polygon zeichnen',
                'rectangle'    => 'Ein Rechteck zeichnen',
                'circle'       => 'Einen Kreis zeichnen',
                'marker'       => 'Einen Marker zeichnen',
                'circlemarker' => 'Einen Kreismarker zeichnen',
            ],
        ],
        'handlers' => [
            'circle' => [
                'tooltip' => [
                    'start' => 'Klicken und ziehen, um einen Kreis zu zeichnen.',
                ],
                'radius' => 'Radius',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Karte anklicken, um den Kreismarker zu platzieren.',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => 'Karte anklicken, um den Marker zu platzieren.',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'start' => 'Klicken, um mit dem Zeichnen der Form zu beginnen.',
                    'cont'  => 'Klicken, um die Form weiter zu zeichnen.',
                    'end'   => 'Klicken Sie auf den ersten Punkt, um diese Form zu schließen.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Fehler:</strong> Formkanten dürfen sich nicht kreuzen!',
                'tooltip' => [
                    'start' => 'Klicken, um mit dem Zeichnen der Linie zu beginnen.',
                    'cont'  => 'Klicken, um die Linie weiter zu zeichnen.',
                    'end'   => 'Klicken Sie auf den letzten Punkt, um die Linie zu beenden.',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => 'Klicken und ziehen, um ein Rechteck zu zeichnen.',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => 'Maus loslassen, um das Zeichnen zu beenden.',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'start' => 'Klicken, um mit dem Zeichnen des Pfades zu beginnen.',
                    'cont'  => 'Klicken, um den Pfad weiter zu zeichnen.',
                    'end'   => 'Klicken Sie auf die Schaltfläche \'Fertig\' in der Symbolleiste, um Ihren Pfad zu vervollständigen.',
                ],
            ],
            'brushline' => [
                'tooltip' => [
                    'start' => 'Klicken, um mit dem Zeichnen der Linie zu beginnen.',
                    'cont'  => 'Klicken und ziehen, um die Linie weiter zu zeichnen.',
                    'end'   => 'Klicken/Ziehen Sie weiter. Wenn Sie fertig sind, drücken Sie die Schaltfläche \'Fertig\' in der Symbolleiste, um Ihre Linie zu vervollständigen.',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar' => [
            'actions' => [
                'save' => [
                    'title' => 'Änderungen speichern',
                    'text'  => 'Speichern',
                ],
                'cancel' => [
                    'title' => 'Bearbeitung abbrechen, verwirft alle Änderungen',
                    'text'  => 'Abbrechen',
                ],
                'clearAll' => [
                    'title' => 'Alle Ebenen löschen',
                    'text'  => 'Alle löschen',
                ],
            ],
            'buttons' => [
                'edit'           => 'Ebenen bearbeiten',
                'editDisabled'   => 'Keine Ebenen zum Bearbeiten',
                'remove'         => 'Ebenen löschen',
                'removeDisabled' => 'Keine Ebenen zum Löschen',
            ],
        ],
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'text'    => 'Ziehen Sie Griffe oder Marker, um Merkmale zu bearbeiten.',
                    'subtext' => 'Klicken Sie auf Abbrechen, um Änderungen rückgängig zu machen.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Klicken Sie auf ein Merkmal, um es zu entfernen.',
                ],
            ],
        ],
    ],

];
