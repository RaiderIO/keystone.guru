<?php

return [

    'draw' => [
        'toolbar'  => [
            'actions' => [
                'title' => 'Annuler le dessin',
                'text'  => 'Annuler',
            ],
            'finish'  => [
                'title' => 'Terminer le dessin',
                'text'  => 'Terminer',
            ],
            'undo'    => [
                'title' => 'Supprimer le dernier point dessiné',
                'text'  => 'Supprimer le dernier point',
            ],
            'buttons' => [
                'polyline'     => 'Dessiner une polyligne',
                'polygon'      => 'Dessiner un polygone',
                'rectangle'    => 'Dessiner un rectangle',
                'circle'       => 'Dessiner un cercle',
                'marker'       => 'Dessiner un marqueur',
                'circlemarker' => 'Dessiner un marqueur circulaire',
            ],
        ],
        'handlers' => [
            'circle'       => [
                'tooltip' => [
                    'start' => 'Cliquez et faites glisser pour dessiner un cercle.',
                ],
                'radius'  => 'Rayon',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => 'Cliquez sur la carte pour placer un marqueur circulaire.',
                ],
            ],
            'marker'       => [
                'tooltip' => [
                    'start' => 'Cliquez sur la carte pour placer un marqueur.',
                ],
            ],
            'polygon'      => [
                'tooltip' => [
                    'start' => 'Cliquez pour commencer à dessiner la forme.',
                    'cont'  => 'Cliquez pour continuer à dessiner la forme.',
                    'end'   => 'Cliquez sur le premier point pour fermer cette forme.',
                ],
            ],
            'polyline'     => [
                'error'   => '<strong>Erreur:</strong> les bords de la forme ne peuvent pas se croiser !',
                'tooltip' => [
                    'start' => 'Cliquez pour commencer à dessiner la ligne.',
                    'cont'  => 'Cliquez pour continuer à dessiner la ligne.',
                    'end'   => 'Cliquez sur le dernier point pour terminer la ligne.',
                ],
            ],
            'rectangle'    => [
                'tooltip' => [
                    'start' => 'Cliquez et faites glisser pour dessiner un rectangle.',
                ],
            ],
            'simpleshape'  => [
                'tooltip' => [
                    'end' => 'Relâchez la souris pour terminer le dessin.',
                ],
            ],
            'path'         => [
                'tooltip' => [
                    'start' => 'Cliquez pour commencer à dessiner le chemin.',
                    'cont'  => 'Cliquez pour continuer à dessiner le chemin.',
                    'end'   => 'Cliquez sur le bouton \'Terminer\' dans la barre d\'outils pour compléter votre chemin.',
                ],
            ],
            'brushline'    => [
                'tooltip' => [
                    'start' => 'Cliquez pour commencer à dessiner la ligne.',
                    'cont'  => 'Cliquez et faites glisser pour continuer à dessiner la ligne.',
                    'end'   => 'Continuez à cliquer/glisser, une fois terminé, appuyez sur le bouton \'Terminer\' dans la barre d\'outils pour compléter votre ligne.',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar'  => [
            'actions' => [
                'save'     => [
                    'title' => 'Enregistrer les modifications',
                    'text'  => 'Enregistrer',
                ],
                'cancel'   => [
                    'title' => 'Annuler l\'édition, annule toutes les modifications',
                    'text'  => 'Annuler',
                ],
                'clearAll' => [
                    'title' => 'Effacer toutes les couches',
                    'text'  => 'Tout effacer',
                ],
            ],
            'buttons' => [
                'edit'           => 'Modifier les couches',
                'editDisabled'   => 'Pas de couches à éditer',
                'remove'         => 'Supprimer les couches',
                'removeDisabled' => 'Pas de couches à supprimer',
            ],
        ],
        'handlers' => [
            'edit'   => [
                'tooltip' => [
                    'text'    => 'Faites glisser les poignées ou les marqueurs pour modifier les fonctionnalités.',
                    'subtext' => 'Cliquez sur annuler pour annuler les modifications.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => 'Cliquez sur une fonctionnalité pour la supprimer.',
                ],
            ],
        ],
    ],

];
