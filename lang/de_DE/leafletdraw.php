<?php

return [
    'draw' => [
        'handlers' => [
            'brushline' => [
                'tooltip' => [
                    'cont'  => 'Klicken und ziehen, um die Linie weiter zu zeichnen.',
                    'end'   => 'Klicken/Ziehen Sie weiter. Wenn Sie fertig sind, drücken Sie die Schaltfläche \'Fertig\' in der Symbolleiste, um Ihre Linie zu vervollständigen.',
                    'start' => 'Klicken, um mit dem Zeichnen der Linie zu beginnen.',
                ],
            ],
            'circle' => [
                'radius'  => 'Radius',
                'tooltip' => [
                    'start' => 'Klicken und ziehen, um einen Kreis zu zeichnen.',
                ],
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
            'path' => [
                'tooltip' => [
                    'cont'  => 'Klicken, um den Pfad weiter zu zeichnen.',
                    'end'   => 'Klicken Sie auf die Schaltfläche \'Fertig\' in der Symbolleiste, um Ihren Pfad zu vervollständigen.',
                    'start' => 'Klicken, um mit dem Zeichnen des Pfades zu beginnen.',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'cont'  => 'Klicken, um die Form weiter zu zeichnen.',
                    'end'   => 'Klicken Sie auf den ersten Punkt, um diese Form zu schließen.',
                    'start' => 'Klicken, um mit dem Zeichnen der Form zu beginnen.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Fehler:</strong> Formkanten dürfen sich nicht kreuzen!',
                'tooltip' => [
                    'cont'  => 'Klicken, um die Linie weiter zu zeichnen.',
                    'end'   => 'Klicken Sie auf den letzten Punkt, um die Linie zu beenden.',
                    'start' => 'Klicken, um mit dem Zeichnen der Linie zu beginnen.',
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
        ],
        'toolbar' => [
            'actions' => [
                'text'  => 'Abbrechen',
                'title' => 'Zeichnen abbrechen',
            ],
            'buttons' => [
                'circle'       => 'Einen Kreis zeichnen',
                'circlemarker' => 'Einen Kreismarker zeichnen',
                'marker'       => 'Einen Marker zeichnen',
                'polygon'      => 'Ein Polygon zeichnen',
                'polyline'     => 'Eine Polylinie zeichnen',
                'rectangle'    => 'Ein Rechteck zeichnen',
            ],
            'finish' => [
                'text'  => 'Fertig',
                'title' => 'Zeichnen beenden',
            ],
            'undo' => [
                'text'  => 'Letzten Punkt löschen',
                'title' => 'Zuletzt gezeichneten Punkt löschen',
            ],
        ],
    ],
    'edit' => [
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'subtext' => 'Klicken Sie auf Abbrechen, um Änderungen rückgängig zu machen.',
                    'text'    => 'Ziehen Sie Griffe oder Marker, um Merkmale zu bearbeiten.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Klicken Sie auf ein Merkmal, um es zu entfernen.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'cancel' => [
                    'text'  => 'Abbrechen',
                    'title' => 'Bearbeitung abbrechen, verwirft alle Änderungen',
                ],
                'clearAll' => [
                    'text'  => 'Alle löschen',
                    'title' => 'Alle Ebenen löschen',
                ],
                'save' => [
                    'text'  => 'Speichern',
                    'title' => 'Änderungen speichern',
                ],
            ],
            'buttons' => [
                'edit'           => 'Ebenen bearbeiten',
                'editDisabled'   => 'Keine Ebenen zum Bearbeiten',
                'remove'         => 'Ebenen löschen',
                'removeDisabled' => 'Keine Ebenen zum Löschen',
            ],
        ],
    ],
];
