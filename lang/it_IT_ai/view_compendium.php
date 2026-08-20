<?php

return [
    'index' => [
        'title'       => 'Compendio',
        'header'      => 'Compendio',
        'intro'       => 'Il Compendio è un\'enciclopedia alimentata dalla community di tutte le spedizioni della stagione attuale del gioco. Scopri esattamente cosa fa ogni NPC, quali incantesimi lancia, come contrastarlo e quale controllo della folla funziona su di lui.',
        'data_source' => [
            'title'       => 'Sempre aggiornato',
            'description' => 'Il Compendio viene mantenuto in tempo reale dai log di combattimento che i giocatori caricano automaticamente tramite il client di Raider.IO. Ogni run tracciata migliora silenziosamente i dati per tutti.',
            'cta'         => 'Installa il client di Raider.IO',
        ],
        'how_it_works' => [
            'title'  => 'Come funziona',
            'step_1' => [
                'title'       => 'Scegli una sezione',
                'description' => 'Sfoglia NPC, Incantesimi, Attività recente, oppure vai direttamente al controllo della folla per classe.',
            ],
            'step_2' => [
                'title'       => 'Cerca e filtra',
                'description' => 'Filtra per dungeon per concentrarti esattamente sui pull a cui ti stai preparando.',
            ],
            'step_3' => [
                'title'       => 'Approfondisci i dettagli',
                'description' => 'Apri qualsiasi NPC o incantesimo per vedere scuole, tipi di dissoluzione, meccaniche, durate e altro.',
            ],
        ],
        'cards' => [
            'npc' => [
                'title'        => 'NPC',
                'description'  => 'Ogni NPC del gioco con le sue abilità, salute, classificazione e le spedizioni in cui appare.',
                'cta'          => 'Sfoglia NPC',
                'count_suffix' => 'NPC catalogati',
            ],
            'spell' => [
                'title'        => 'Incantesimi',
                'description'  => 'Cerca qualsiasi incantesimo per vedere cosa fa, come evitarlo e quali NPC lo lanciano.',
                'cta'          => 'Sfoglia incantesimi',
                'count_suffix' => 'incantesimi catalogati',
            ],
            'activity' => [
                'title'       => 'Attività',
                'description' => 'Un feed in tempo reale dei dati più recenti provenienti dalla community, organizzati per giorno.',
                'cta'         => 'Visualizza attività',
                'subtitle'    => 'Aggiornato ogni giorno',
            ],
            'class' => [
                'title'        => 'Per classe',
                'description'  => 'Scopri quali dei tuoi incantesimi di controllo della folla funzionano su quali NPC, raggruppati per classe.',
                'cta'          => 'Sfoglia per classe',
                'count_suffix' => 'classi coperte',
            ],
        ],
    ],
    'event' => [
        'characteristic_added'    => 'Influenzato da :name',
        'characteristic_removed'  => 'Non influenzato da :name',
        'spell_assigned'          => 'Lancia :name',
        'spell_created'           => ':spell aggiunto al database',
        'property_changed'        => 'Influenzato da :property',
        'property_removed'        => 'Non influenzato da :property',
        'counter_added'           => ':spell può ora essere contrastato da :property',
        'counter_removed'         => ':spell non può più essere contrastato da :property',
        'school_recorded'         => ':spell infligge danni di tipo :schools',
        'immunity_bypass_added'   => ':spell è stato osservato mentre colpiva attraverso :property',
        'immunity_bypass_removed' => ':spell non è stato più osservato mentre colpiva attraverso :property',
        // Subject-less variants: used when the row already leads with the spell link as its
        // subject, so the description does not repeat the spell name
        'spell_created_no_subject'           => 'Aggiunto al database',
        'counter_added_no_subject'           => 'Può ora essere contrastato da :property',
        'counter_removed_no_subject'         => 'Non può più essere contrastato da :property',
        'school_recorded_no_subject'         => 'Infligge danni di tipo :schools',
        'immunity_bypass_added_no_subject'   => 'Osservato mentre colpiva attraverso :property',
        'immunity_bypass_removed_no_subject' => 'Non più osservato mentre colpiva attraverso :property',
        'count'                              => ':count evento|:count eventi',
        'more'                               => 'e altri :count',
        'property'                           => [
            'aura'   => 'Aura',
            'debuff' => 'Debuff',
        ],
    ],
    'npc' => [
        'index' => [
            'title'                 => 'Compendio NPC',
            'header'                => 'Compendio NPC',
            'boss'                  => 'Boss',
            'table_header_name'     => 'Nome',
            'table_header_dungeons' => 'Spedizioni',
            'table_header_spells'   => 'Incantesimi',
        ],
        'show' => [
            'title'   => ':name - Compendio NPC',
            'wowhead' => 'Visualizza su Wowhead',
        ],
        'sections' => [
            'header' => [
                'level' => 'Livello',
            ],
            'characteristics' => [
                'title'        => 'Caratteristiche',
                'tooltip'      => 'Da cosa è influenzato questo NPC?',
                'empty'        => 'Nessuna caratteristica registrata.',
                'not_observed' => 'Non osservato:',
            ],
            'spells' => [
                'title'                              => 'Incantesimi',
                'empty'                              => 'Nessun incantesimo registrato.',
                'header_name'                        => 'Nome',
                'header_schools'                     => 'Scuole',
                'header_schools_tooltip'             => 'Che tipo di danno infligge questo incantesimo?',
                'header_miss_types'                  => 'Tipi di fallo',
                'header_miss_types_tooltip'          => 'Cosa puoi fare per evitare questo incantesimo?',
                'header_counters'                    => 'Contromisure',
                'header_counters_tooltip'            => 'Abilità dei giocatori che possono far fallire o ricollocare il bersaglio di questo incantesimo.',
                'header_bypasses_immunities'         => 'Aggira l\'immunità',
                'header_bypasses_immunities_tooltip' => 'Immunità dei giocatori che non fermano questo incantesimo - è stato osservato colpire mentre erano attive.',
                'header_dispel_type'                 => 'Tipo di dissoluzione',
                'header_dispel_type_tooltip'         => 'Che tipo di dissoluzione può essere usato per rimuovere questo incantesimo?',
                'header_mechanic'                    => 'Meccanica',
                'header_cast_time'                   => 'Tempo di lancio',
                'header_duration'                    => 'Durata',
            ],
            'event_feed' => [
                'title' => 'Attività recente',
                'empty' => 'Nessuna attività registrata ancora.',
            ],
        ],
    ],
    'spell' => [
        'index' => [
            'title'                 => 'Compendio incantesimi',
            'header'                => 'Compendio incantesimi',
            'table_header_name'     => 'Nome',
            'table_header_dungeons' => 'Spedizioni',
            'table_header_used_by'  => 'Usato da',
        ],
        'show' => [
            'title'   => ':name - Compendio incantesimi',
            'wowhead' => 'Visualizza su Wowhead',
        ],
        'sections' => [
            'header' => [
                'aura'   => 'Aura',
                'debuff' => 'Debuff',
            ],
            'description' => [
                'title' => 'Descrizione',
            ],
            'details' => [
                'title'                              => 'Dettagli',
                'header_schools'                     => 'Scuole',
                'header_schools_tooltip'             => 'Che tipo di danno infligge questo incantesimo?',
                'header_miss_types'                  => 'Tipi di fallo',
                'header_miss_types_tooltip'          => 'Cosa puoi fare per evitare questo incantesimo?',
                'header_counters'                    => 'Contromisure',
                'header_counters_tooltip'            => 'Abilità dei giocatori che possono far fallire o ricollocare il bersaglio di questo incantesimo.',
                'header_bypasses_immunities'         => 'Aggira l\'immunità',
                'header_bypasses_immunities_tooltip' => 'Immunità dei giocatori che non fermano questo incantesimo - è stato osservato colpire mentre erano attive.',
                'header_dispel_type'                 => 'Tipo di dissoluzione',
                'header_dispel_type_tooltip'         => 'Che tipo di dissoluzione può essere usato per rimuovere questo incantesimo?',
                'header_mechanic'                    => 'Meccanica',
                'header_cast_time'                   => 'Tempo di lancio',
                'header_duration'                    => 'Durata',
            ],
            'dungeons' => [
                'title'       => 'Spedizioni',
                'empty'       => 'Non collegato a nessuna spedizione.',
                'header_name' => 'Nome',
            ],
            'npcs' => [
                'title'                 => 'Usato da',
                'empty'                 => 'Nessun NPC registrato.',
                'header_name'           => 'Nome',
                'header_classification' => 'Classificazione',
                'header_dungeons'       => 'Spedizioni',
            ],
            'event_feed' => [
                'title' => 'Attività recente',
                'empty' => 'Nessuna attività registrata ancora.',
            ],
        ],
    ],
    'activity' => [
        'index' => [
            'title'  => 'Attività del compendio',
            'header' => 'Attività del compendio',
            'empty'  => 'Nessuna attività registrata ancora.',
        ],
        'day' => [
            'title'  => ':date - Attività del compendio',
            'header' => 'Attività del compendio per :date',
            'empty'  => 'Nessuna attività registrata per questo giorno.',
        ],
    ],
    'class' => [
        'index' => [
            'title'  => 'Compendio - Per classe',
            'header' => 'Per classe',
        ],
        'show' => [
            'title'                       => ':name - Per classe',
            'table_header_spell'          => 'Incantesimo',
            'table_header_characteristic' => 'Caratteristica',
            'table_header_npcs'           => 'NPC notevoli',
            'no_spells'                   => 'Nessun incantesimo di controllo della folla trovato per questa classe in questa versione di gioco.',
            'no_npcs'                     => '-',
            'npcs_no_effect'              => 'Immune',
            'npcs_works_on'               => 'Funziona su',
            'npcs_no_exceptions'          => 'Nulla di inaspettato',
            'npcs_no_data'                => 'Nessun dato',
            'npcs_description'            => 'Sono elencate solo le sorprese - trash che ha resistito e boss su cui ha comunque avuto effetto. Tutto ciò che si comporta come già ti aspetti viene omesso. "Nessun effetto osservato" significa che altri controlli della folla di questa tabella sono stati visti colpire quell\'NPC, ma questo non lo ha mai fatto: è una prova, non un\'immunità confermata.',
            'counters'                    => [
                'title'              => 'Abilità contrastabili',
                'racial'             => 'Razziale (:race)',
                'no_spells'          => 'Nessun incantesimo NPC contrastabile trovato per questa spedizione.',
                'table_header_spell' => 'Incantesimo',
                'table_header_npcs'  => 'NPC',
            ],
            'reflect' => [
                'title'              => 'Incantesimi riflettibili',
                'description'        => 'Incantesimi NPC di questa spedizione che sono stati osservati essere riflessi.',
                'no_spells'          => 'Nessun incantesimo NPC riflettibile trovato per questa spedizione.',
                'table_header_spell' => 'Incantesimo',
                'table_header_npcs'  => 'NPC',
            ],
        ],
    ],
];
