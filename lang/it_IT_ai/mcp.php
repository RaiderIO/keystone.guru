<?php

return [
    'error' => [
        'unauthenticated'                   => 'Non autenticato. Connettiti con un token di accesso OAuth.',
        'feature_inactive'                  => 'Il server MCP di keystone.guru non è disponibile per il tuo account.',
        'missing_scope'                     => 'Al tuo token di accesso manca il permesso :scope richiesto da questo strumento. Riconnettiti e concedilo.',
        'rate_limited'                      => 'Troppe richieste per questo strumento. Riprova tra :seconds s.',
        'request_refused'                   => 'La richiesta è stata rifiutata.',
        'dungeon_not_found'                 => 'Nessun dungeon attivo corrisponde a ":dungeon". Usa list_dungeons per le chiavi valide.',
        'route_not_found'                   => 'Non hai nessun percorso con la chiave ":route". Usa list_my_routes per i tuoi percorsi.',
        'pull_not_found'                    => 'Il percorso non ha il pull :pull.',
        'unable_to_save_route'              => 'Non è stato possibile salvare il percorso.',
        'unable_to_save_pull'               => 'Non è stato possibile salvare il pull.',
        'unable_to_delete_pull'             => 'Non è stato possibile rimuovere il pull.',
        'no_enemies_given'                  => 'Indica almeno un ID pacchetto o un ID nemico.',
        'unknown_pack_ids'                  => 'Questi ID pacchetto non fanno parte della mappatura del dungeon del percorso: :ids.',
        'unknown_enemy_ids'                 => 'Questi ID nemico non fanno parte della mappatura del dungeon del percorso: :ids.',
        'enemies_already_pulled'            => 'Questi nemici sono già in un altro pull (ID nemico => numero del pull): :enemies. Rimuovili prima da quel pull, oppure escludili.',
        'unknown_spell_ids'                 => 'Questi ID incantesimo non possono essere lanciati in un pull: :ids. Usa get_route_options per gli incantesimi che un pull può avere.',
        'unknown_floor'                     => 'Il piano :floor non è un piano del dungeon del percorso. Usa list_dungeons per i piani.',
        'unknown_dungeon_difficulty'        => 'La difficoltà :difficulty non è una difficoltà speedrun di questo dungeon. Usa list_dungeons per le difficoltà di ogni dungeon.',
        'unknown_dungeon_floor'             => 'Il piano :floor non è un piano del dungeon. Usa list_dungeons per i piani.',
        'unknown_dungeon_start'             => 'L\'inizio dungeon :start non appartiene alla mappatura del dungeon del percorso. Usa get_route_options con il dungeon per i suoi inizi dungeon.',
        'specialization_not_of_class'       => 'Il membro del gruppo :member ha una specializzazione di un\'altra classe.',
        'specialization_not_of_spell_class' => 'La specializzazione :specialization non è una specializzazione della classe :class. Usa get_route_options per le specializzazioni di ogni classe.',
        'reorder_mismatch'                  => 'L\'ordine deve elencare ogni ID pull del percorso esattamente una volta. ID pull del percorso: :ids.',
    ],
];
