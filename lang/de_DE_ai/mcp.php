<?php

return [
    'error' => [
        'unauthenticated'                   => 'Nicht authentifiziert. Verbinde dich mit einem OAuth-Zugriffstoken.',
        'feature_inactive'                  => 'Der keystone.guru-MCP-Server ist für dein Konto nicht verfügbar.',
        'missing_scope'                     => 'Deinem Zugriffstoken fehlt die Berechtigung :scope, die dieses Tool benötigt. Verbinde dich erneut und erteile sie.',
        'rate_limited'                      => 'Zu viele Anfragen für dieses Tool. Versuche es in :seconds Sek. erneut.',
        'request_refused'                   => 'Die Anfrage wurde abgelehnt.',
        'dungeon_not_found'                 => 'Kein aktiver Dungeon entspricht ":dungeon". Nutze list_dungeons für die gültigen Schlüssel.',
        'route_not_found'                   => 'Du hast keine Route mit dem Schlüssel ":route". Nutze list_my_routes für deine Routen.',
        'pull_not_found'                    => 'Die Route hat keinen Pull :pull.',
        'unable_to_save_route'              => 'Die Route konnte nicht gespeichert werden.',
        'unable_to_save_pull'               => 'Der Pull konnte nicht gespeichert werden.',
        'unable_to_delete_pull'             => 'Der Pull konnte nicht entfernt werden.',
        'no_enemies_given'                  => 'Gib mindestens eine Pack-ID oder Gegner-ID an.',
        'unknown_pack_ids'                  => 'Diese Pack-IDs gehören nicht zum Dungeon-Mapping der Route: :ids.',
        'unknown_enemy_ids'                 => 'Diese Gegner-IDs gehören nicht zum Dungeon-Mapping der Route: :ids.',
        'enemies_already_pulled'            => 'Diese Gegner sind bereits in einem anderen Pull (Gegner-ID => Pull-Nummer): :enemies. Entferne sie zuerst aus diesem Pull oder lass sie weg.',
        'unknown_spell_ids'                 => 'Diese Zauber-IDs können in keinem Pull gewirkt werden: :ids. Nutze get_route_options für die Zauber, die ein Pull haben kann.',
        'unknown_floor'                     => 'Etage :floor ist keine Etage des Dungeons der Route. Nutze list_dungeons für die Etagen.',
        'unknown_dungeon_difficulty'        => 'Schwierigkeit :difficulty ist keine Speedrun-Schwierigkeit dieses Dungeons. Nutze list_dungeons für die Schwierigkeiten jedes Dungeons.',
        'unknown_dungeon_floor'             => 'Etage :floor ist keine Etage des Dungeons. Nutze list_dungeons für die Etagen.',
        'unknown_dungeon_start'             => 'Dungeonstart :start gehört nicht zum Dungeon-Mapping der Route. Nutze get_route_options mit dem Dungeon für seine Dungeonstarts.',
        'specialization_not_of_class'       => 'Gruppenmitglied :member hat eine Spezialisierung einer anderen Klasse.',
        'specialization_not_of_spell_class' => 'Spezialisierung :specialization ist keine Spezialisierung der Klasse :class. Nutze get_route_options für die Spezialisierungen jeder Klasse.',
        'reorder_mismatch'                  => 'Die Reihenfolge muss jede Pull-ID der Route genau einmal enthalten. Pull-IDs der Route: :ids.',
    ],
];
