<?php

return [
    'draw' => [
        'handlers' => [
            'brushline' => [
                'tooltip' => [
                    'cont'  => 'Cliquez et faites glisser pour continuer à dessiner la ligne.',
                    'end'   => 'Continuez à cliquer/glisser, une fois terminé, appuyez sur le bouton \'Terminer\' dans la barre d\'outils pour compléter votre ligne.',
                    'start' => 'Cliquez pour commencer à dessiner la ligne.',
                ],
            ],
            'circle' => [
                'radius'  => 'Rayon',
                'tooltip' => [
                    'start' => 'Cliquez et faites glisser pour dessiner un cercle.',
                ],
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Cliquez sur la carte pour placer un marqueur circulaire.',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => 'Cliquez sur la carte pour placer un marqueur.',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'cont'  => 'Cliquez pour continuer à dessiner le chemin.',
                    'end'   => 'Cliquez sur le bouton \'Terminer\' dans la barre d\'outils pour compléter votre chemin.',
                    'start' => 'Cliquez pour commencer à dessiner le chemin.',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'cont'  => 'Cliquez pour continuer à dessiner la forme.',
                    'end'   => 'Cliquez sur le premier point pour fermer cette forme.',
                    'start' => 'Cliquez pour commencer à dessiner la forme.',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>Erreur:</strong> les bords de la forme ne peuvent pas se croiser !',
                'tooltip' => [
                    'cont'  => 'Cliquez pour continuer à dessiner la ligne.',
                    'end'   => 'Cliquez sur le dernier point pour terminer la ligne.',
                    'start' => 'Cliquez pour commencer à dessiner la ligne.',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => 'Cliquez et faites glisser pour dessiner un rectangle.',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => 'Relâchez la souris pour terminer le dessin.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'text'  => 'Annuler',
                'title' => 'Annuler le dessin',
            ],
            'buttons' => [
                'circle'       => 'Dessiner un cercle',
                'circlemarker' => 'Dessiner un marqueur circulaire',
                'marker'       => 'Dessiner un marqueur',
                'polygon'      => 'Dessiner un polygone',
                'polyline'     => 'Dessiner une polyligne',
                'rectangle'    => 'Dessiner un rectangle',
            ],
            'finish' => [
                'text'  => 'Terminer',
                'title' => 'Terminer le dessin',
            ],
            'undo' => [
                'text'  => 'Supprimer le dernier point',
                'title' => 'Supprimer le dernier point dessiné',
            ],
        ],
    ],
    'edit' => [
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'subtext' => 'Cliquez sur annuler pour annuler les modifications.',
                    'text'    => 'Faites glisser les poignées ou les marqueurs pour modifier les fonctionnalités.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Cliquez sur une fonctionnalité pour la supprimer.',
                ],
            ],
        ],
        'toolbar' => [
            'actions' => [
                'cancel' => [
                    'text'  => 'Annuler',
                    'title' => 'Annuler l\'édition, annule toutes les modifications',
                ],
                'clearAll' => [
                    'text'  => 'Tout effacer',
                    'title' => 'Effacer toutes les couches',
                ],
                'save' => [
                    'text'  => 'Enregistrer',
                    'title' => 'Enregistrer les modifications',
                ],
            ],
            'buttons' => [
                'edit'           => 'Modifier les couches',
                'editDisabled'   => 'Pas de couches à éditer',
                'remove'         => 'Supprimer les couches',
                'removeDisabled' => 'Pas de couches à supprimer',
            ],
        ],
    ],
];
