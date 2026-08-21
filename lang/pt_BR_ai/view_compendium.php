<?php

return [
    'index' => [
        'title'       => 'Compêndio',
        'header'      => 'Compêndio',
        'intro'       => 'O Compêndio é uma enciclopédia mantida pela comunidade de todas as masmorras da temporada atual do jogo. Consulte exatamente o que cada NPC faz, quais feitiços eles conjuram, como neutralizá-los e qual controle de multidão funciona neles.',
        'data_source' => [
            'title'       => 'Sempre atualizado',
            'description' => 'O Compêndio é mantido em tempo real por logs de combate que os jogadores enviam automaticamente através do cliente Raider.IO. Toda run rastreada melhora silenciosamente os dados para todos.',
            'cta'         => 'Instalar o cliente Raider.IO',
        ],
        'how_it_works' => [
            'title'  => 'Como funciona',
            'step_1' => [
                'title'       => 'Escolha uma seção',
                'description' => 'Navegue por NPCs, Feitiços, Atividade recente, ou vá direto ao controle de multidão por classe.',
            ],
            'step_2' => [
                'title'       => 'Busque e filtre',
                'description' => 'Filtre por masmorra para focar exatamente nos pulls para os quais você está se preparando.',
            ],
            'step_3' => [
                'title'       => 'Aprofunde-se nos detalhes',
                'description' => 'Abra qualquer NPC ou feitiço para ver escolas, tipos de dissipação, mecânicas, durações e mais.',
            ],
        ],
        'cards' => [
            'npc' => [
                'title'        => 'NPCs',
                'description'  => 'Todo NPC do jogo com suas habilidades, vida, classificação e as masmorras em que aparecem.',
                'cta'          => 'Navegar por NPCs',
                'count_suffix' => 'NPCs catalogados',
            ],
            'spell' => [
                'title'        => 'Feitiços',
                'description'  => 'Consulte qualquer feitiço para ver o que ele faz, como evitá-lo e quais NPCs o conjuram.',
                'cta'          => 'Navegar por feitiços',
                'count_suffix' => 'feitiços catalogados',
            ],
            'activity' => [
                'title'       => 'Atividade',
                'description' => 'Um feed ao vivo dos dados mais recentes enviados pela comunidade, organizado por dia.',
                'cta'         => 'Ver atividade',
                'subtitle'    => 'Atualizado diariamente',
            ],
            'class' => [
                'title'        => 'Por Classe',
                'description'  => 'Veja quais dos seus feitiços de controle de multidão funcionam em quais NPCs, agrupados por classe.',
                'cta'          => 'Navegar por classe',
                'count_suffix' => 'classes cobertas',
            ],
        ],
    ],
    'event' => [
        'characteristic_added'    => 'Afetado por :name',
        'characteristic_removed'  => 'Não afetado por :name',
        'spell_assigned'          => 'Conjura :name',
        'spell_created'           => ':spell adicionado ao banco de dados',
        'property_changed'        => 'Afetado por :property',
        'property_removed'        => 'Não afetado por :property',
        'counter_added'           => ':spell agora pode ser neutralizado por :property',
        'counter_removed'         => ':spell não pode mais ser neutralizado por :property',
        'school_recorded'         => ':spell causa dano de :schools',
        'immunity_bypass_added'   => ':spell foi observado acertando apesar de :property',
        'immunity_bypass_removed' => ':spell não foi mais observado acertando apesar de :property',
        // Subject-less variants: used when the row already leads with the spell link as its
        // subject, so the description does not repeat the spell name
        'spell_created_no_subject'           => 'Adicionado ao banco de dados',
        'counter_added_no_subject'           => 'Agora pode ser neutralizado por :property',
        'counter_removed_no_subject'         => 'Não pode mais ser neutralizado por :property',
        'school_recorded_no_subject'         => 'Causa dano de :schools',
        'immunity_bypass_added_no_subject'   => 'Observado acertando apesar de :property',
        'immunity_bypass_removed_no_subject' => 'Não observado mais acertando apesar de :property',
        'count'                              => ':count evento|:count eventos',
        'more'                               => 'e mais :count',
        'property'                           => [
            'aura'   => 'Aura',
            'debuff' => 'Debuff',
        ],
    ],
    'npc' => [
        'index' => [
            'title'                 => 'Compêndio de NPCs',
            'header'                => 'Compêndio de NPCs',
            'boss'                  => 'Chefe',
            'table_header_name'     => 'Nome',
            'table_header_dungeons' => 'Masmorras',
            'table_header_spells'   => 'Feitiços',
        ],
        'show' => [
            'title'   => ':name - Compêndio de NPCs',
            'wowhead' => 'Ver no Wowhead',
        ],
        'sections' => [
            'header' => [
                'level' => 'Nível',
            ],
            'characteristics' => [
                'title'        => 'Características',
                'tooltip'      => 'Pelo que este NPC é afetado?',
                'empty'        => 'Nenhuma característica registrada.',
                'not_observed' => 'Não observado:',
            ],
            'spells' => [
                'title'                              => 'Feitiços',
                'empty'                              => 'Nenhum feitiço registrado.',
                'header_name'                        => 'Nome',
                'header_schools'                     => 'Escolas',
                'header_schools_tooltip'             => 'Que tipo de dano este feitiço causa?',
                'header_miss_types'                  => 'Tipos de falha',
                'header_miss_types_tooltip'          => 'O que você pode fazer para evitar este feitiço?',
                'header_counters'                    => 'Contramedidas',
                'header_counters_tooltip'            => 'Habilidades de jogador que podem fazer este feitiço falhar ou trocar de alvo.',
                'header_bypasses_immunities'         => 'Ignora imunidade',
                'header_bypasses_immunities_tooltip' => 'Imunidades de jogador que não impedem este feitiço - ele foi observado acertando enquanto elas estavam ativas.',
                'header_dispel_type'                 => 'Tipo de dissipação',
                'header_dispel_type_tooltip'         => 'Que tipo de dissipação pode ser usado para remover este feitiço?',
                'header_mechanic'                    => 'Mecânica',
                'header_cast_time'                   => 'Tempo de conjuração',
                'header_duration'                    => 'Duração',
            ],
            'event_feed' => [
                'title' => 'Atividade recente',
                'empty' => 'Nenhuma atividade registrada ainda.',
            ],
        ],
    ],
    'spell' => [
        'index' => [
            'title'                 => 'Compêndio de Feitiços',
            'header'                => 'Compêndio de Feitiços',
            'table_header_name'     => 'Nome',
            'table_header_dungeons' => 'Masmorras',
            'table_header_used_by'  => 'Usado por',
        ],
        'show' => [
            'title'   => ':name - Compêndio de Feitiços',
            'wowhead' => 'Ver no Wowhead',
        ],
        'sections' => [
            'header' => [
                'aura'   => 'Aura',
                'debuff' => 'Debuff',
            ],
            'description' => [
                'title' => 'Descrição',
            ],
            'details' => [
                'title'                              => 'Detalhes',
                'header_schools'                     => 'Escolas',
                'header_schools_tooltip'             => 'Que tipo de dano este feitiço causa?',
                'header_miss_types'                  => 'Tipos de falha',
                'header_miss_types_tooltip'          => 'O que você pode fazer para evitar este feitiço?',
                'header_counters'                    => 'Contramedidas',
                'header_counters_tooltip'            => 'Habilidades de jogador que podem fazer este feitiço falhar ou trocar de alvo.',
                'header_bypasses_immunities'         => 'Ignora imunidade',
                'header_bypasses_immunities_tooltip' => 'Imunidades de jogador que não impedem este feitiço - ele foi observado acertando enquanto elas estavam ativas.',
                'header_dispel_type'                 => 'Tipo de dissipação',
                'header_dispel_type_tooltip'         => 'Que tipo de dissipação pode ser usado para remover este feitiço?',
                'header_mechanic'                    => 'Mecânica',
                'header_cast_time'                   => 'Tempo de conjuração',
                'header_duration'                    => 'Duração',
            ],
            'dungeons' => [
                'title'       => 'Masmorras',
                'empty'       => 'Não vinculado a nenhuma masmorra.',
                'header_name' => 'Nome',
            ],
            'npcs' => [
                'title'                 => 'Usado por',
                'empty'                 => 'Nenhum NPC registrado.',
                'header_name'           => 'Nome',
                'header_classification' => 'Classificação',
                'header_dungeons'       => 'Masmorras',
            ],
            'event_feed' => [
                'title' => 'Atividade recente',
                'empty' => 'Nenhuma atividade registrada ainda.',
            ],
        ],
    ],
    'activity' => [
        'index' => [
            'title'  => 'Atividade do Compêndio',
            'header' => 'Atividade do Compêndio',
            'empty'  => 'Nenhuma atividade registrada ainda.',
        ],
        'day' => [
            'title'  => ':date - Atividade do Compêndio',
            'header' => 'Atividade do Compêndio para :date',
            'empty'  => 'Nenhuma atividade registrada para este dia.',
        ],
    ],
    'class' => [
        'index' => [
            'title'  => 'Compêndio - Por Classe',
            'header' => 'Por Classe',
        ],
        'show' => [
            'title'                       => ':name - Por Classe',
            'table_header_spell'          => 'Feitiço',
            'table_header_characteristic' => 'Característica',
            'table_header_npcs'           => 'NPCs notáveis',
            'no_spells'                   => 'Nenhum feitiço de CC encontrado para esta classe nesta versão do jogo.',
            'no_npcs'                     => '-',
            'npcs_no_effect'              => 'Imune',
            'npcs_works_on'               => 'Funciona em',
            'npcs_no_exceptions'          => 'Nada inesperado',
            'npcs_no_data'                => 'Sem dados',
            'npcs_description'            => 'Apenas as surpresas são listadas - trash que resistiu e chefes em que funcionou mesmo assim. Qualquer coisa que se comporte da forma que você já espera é omitida. "Nenhum efeito observado" significa que outro controle de multidão desta tabela foi visto acertando esse NPC, mas este nunca foi: é evidência, não uma imunidade confirmada.',
            'counters'                    => [
                'title'              => 'Habilidades neutralizáveis',
                'racial'             => 'Racial (:race)',
                'no_spells'          => 'Nenhum feitiço de NPC que possa ser neutralizado foi encontrado para esta masmorra.',
                'table_header_spell' => 'Feitiço',
                'table_header_npcs'  => 'NPCs',
            ],
            'reflect' => [
                'title'              => 'Feitiços refletíveis',
                'description'        => 'Feitiços de NPC nesta masmorra que foram observados sendo refletidos.',
                'no_spells'          => 'Nenhum feitiço de NPC refletível encontrado para esta masmorra.',
                'table_header_spell' => 'Feitiço',
                'table_header_npcs'  => 'NPCs',
            ],
        ],
    ],
];
