<?php

return [
    400 => [
        'message' => 'Votre navigateur a envoyé une requête invalide, veuillez réessayer.',
        'title'   => '400 Mauvaise requête',
    ],
    401 => [
        'message' => 'Désolé, vous n\'êtes pas autorisé à accéder à cette page.',
        'title'   => '401 Non autorisé',
    ],
    403 => [
        'message' => 'Désolé, vous n\'êtes pas autorisé à accéder à cette page.',
        'title'   => '403 Interdit',
    ],
    404 => [
        'message' => 'Désolé, la page que vous recherchez est introuvable.',
        'title'   => '404 Non trouvé',
    ],
    410 => [
        'message' => 'Désolé, la page que vous recherchez a expiré.',
        'title'   => '410 Page expirée',
    ],
    419 => [
        'message' => 'Désolé, votre session a expiré. Veuillez actualiser et réessayer.',
        'title'   => '419 Page expirée',
    ],
    429 => [
        'message' => 'Désolé, vous faites trop de requêtes à nos serveurs.',
        'title'   => '429 Trop de requêtes',
    ],
    500 => [
        'message' => 'Oups, quelque chose s\'est mal passé sur nos serveurs.',
        'title'   => '500 Erreur interne du serveur',
    ],
    503 => [
        'message' => 'Keystone.guru est en maintenance. Nous reviendrons bientôt !',
        'title'   => 'Service indisponible',
    ],
];
