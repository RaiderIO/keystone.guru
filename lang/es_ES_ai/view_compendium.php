<?php

return [
    'index' => [
        'title'       => 'Compendio',
        'header'      => 'Compendio',
        'intro'       => 'El Compendio es una enciclopedia impulsada por la comunidad de todas las mazmorras de la temporada actual del juego. Consulta exactamente qué hace cada NPC, qué hechizos lanza, cómo contrarrestarlo y qué controles de masas funcionan contra él.',
        'data_source' => [
            'title'       => 'Siempre actualizado',
            'description' => 'El Compendio se mantiene en tiempo real gracias a los registros de combate que los jugadores suben automáticamente a través del cliente de Raider.IO. Cada partida registrada mejora silenciosamente los datos para todos.',
            'cta'         => 'Instalar el cliente de Raider.IO',
        ],
        'how_it_works' => [
            'title'  => 'Cómo funciona',
            'step_1' => [
                'title'       => 'Elige una sección',
                'description' => 'Explora NPCs, Hechizos, la Actividad reciente, o ve directamente al control de masas por clase.',
            ],
            'step_2' => [
                'title'       => 'Busca y filtra',
                'description' => 'Filtra por mazmorra para centrarte exactamente en los pulls que estás preparando.',
            ],
            'step_3' => [
                'title'       => 'Profundiza en los detalles',
                'description' => 'Abre cualquier NPC o hechizo para ver escuelas, tipos de disipación, mecánicas, duraciones y más.',
            ],
        ],
        'cards' => [
            'npc' => [
                'title'        => 'NPCs',
                'description'  => 'Cada NPC del juego con sus habilidades, salud, clasificación y las mazmorras en las que aparece.',
                'cta'          => 'Explorar NPCs',
                'count_suffix' => 'NPCs catalogados',
            ],
            'spell' => [
                'title'        => 'Hechizos',
                'description'  => 'Consulta cualquier hechizo para ver qué hace, cómo evitarlo y qué NPCs lo lanzan.',
                'cta'          => 'Explorar hechizos',
                'count_suffix' => 'hechizos catalogados',
            ],
            'activity' => [
                'title'       => 'Actividad',
                'description' => 'Un feed en directo de los datos más recientes que llegan de la comunidad, organizados por día.',
                'cta'         => 'Ver actividad',
                'subtitle'    => 'Actualizado a diario',
            ],
            'class' => [
                'title'        => 'Por clase',
                'description'  => 'Consulta cuáles de tus hechizos de control de masas funcionan en qué NPCs, agrupados por clase.',
                'cta'          => 'Explorar por clase',
                'count_suffix' => 'clases cubiertas',
            ],
        ],
    ],
    'event' => [
        'characteristic_added'    => 'Afectado por :name',
        'characteristic_removed'  => 'No afectado por :name',
        'spell_assigned'          => 'Lanza :name',
        'spell_created'           => ':spell añadido a la base de datos',
        'property_changed'        => 'Afectado por :property',
        'property_removed'        => 'No afectado por :property',
        'counter_added'           => ':spell ahora se puede contrarrestar con :property',
        'counter_removed'         => ':spell ya no se puede contrarrestar con :property',
        'school_recorded'         => ':spell inflige daño de :schools',
        'immunity_bypass_added'   => 'Se observó que :spell impactaba a pesar de :property',
        'immunity_bypass_removed' => 'Ya no se observó que :spell impactara a pesar de :property',
        // Subject-less variants: used when the row already leads with the spell link as its
        // subject, so the description does not repeat the spell name
        'spell_created_no_subject'           => 'Añadido a la base de datos',
        'counter_added_no_subject'           => 'Ahora se puede contrarrestar con :property',
        'counter_removed_no_subject'         => 'Ya no se puede contrarrestar con :property',
        'school_recorded_no_subject'         => 'Inflige daño de :schools',
        'immunity_bypass_added_no_subject'   => 'Observado impactando a pesar de :property',
        'immunity_bypass_removed_no_subject' => 'Ya no observado impactando a pesar de :property',
        'count'                              => ':count evento|:count eventos',
        'more'                               => 'y :count más',
        'property'                           => [
            'aura'   => 'Aura',
            'debuff' => 'Debuff',
        ],
    ],
    'npc' => [
        'index' => [
            'title'                 => 'Compendio de NPCs',
            'header'                => 'Compendio de NPCs',
            'boss'                  => 'Jefe',
            'table_header_name'     => 'Nombre',
            'table_header_dungeons' => 'Mazmorras',
            'table_header_spells'   => 'Hechizos',
        ],
        'show' => [
            'title'   => ':name - Compendio de NPCs',
            'wowhead' => 'Ver en Wowhead',
        ],
        'sections' => [
            'header' => [
                'level' => 'Nivel',
            ],
            'characteristics' => [
                'title'        => 'Características',
                'tooltip'      => '¿Por qué se ve afectado este NPC?',
                'empty'        => 'No se han registrado características.',
                'not_observed' => 'No observado:',
            ],
            'spells' => [
                'title'                              => 'Hechizos',
                'empty'                              => 'No se han registrado hechizos.',
                'header_name'                        => 'Nombre',
                'header_schools'                     => 'Escuelas',
                'header_schools_tooltip'             => '¿Qué tipo de daño inflige este hechizo?',
                'header_miss_types'                  => 'Tipos de fallo',
                'header_miss_types_tooltip'          => '¿Qué puedes hacer para evitar este hechizo?',
                'header_counters'                    => 'Contramedidas',
                'header_counters_tooltip'            => 'Habilidades de jugador que pueden hacer fallar este hechizo o cambiar su objetivo.',
                'header_bypasses_immunities'         => 'Omite inmunidad',
                'header_bypasses_immunities_tooltip' => 'Inmunidades de jugador que no detienen este hechizo - se observó que impactaba mientras estaban activas.',
                'header_dispel_type'                 => 'Tipo de disipación',
                'header_dispel_type_tooltip'         => '¿Qué tipo de disipación se puede usar para eliminar este hechizo?',
                'header_mechanic'                    => 'Mecánica',
                'header_cast_time'                   => 'Tiempo de lanzamiento',
                'header_duration'                    => 'Duración',
            ],
            'event_feed' => [
                'title' => 'Actividad reciente',
                'empty' => 'Todavía no se ha registrado actividad.',
            ],
        ],
    ],
    'spell' => [
        'index' => [
            'title'                 => 'Compendio de hechizos',
            'header'                => 'Compendio de hechizos',
            'table_header_name'     => 'Nombre',
            'table_header_dungeons' => 'Mazmorras',
            'table_header_used_by'  => 'Usado por',
        ],
        'show' => [
            'title'   => ':name - Compendio de hechizos',
            'wowhead' => 'Ver en Wowhead',
        ],
        'sections' => [
            'header' => [
                'aura'   => 'Aura',
                'debuff' => 'Debuff',
            ],
            'description' => [
                'title' => 'Descripción',
            ],
            'details' => [
                'title'                              => 'Detalles',
                'header_schools'                     => 'Escuelas',
                'header_schools_tooltip'             => '¿Qué tipo de daño inflige este hechizo?',
                'header_miss_types'                  => 'Tipos de fallo',
                'header_miss_types_tooltip'          => '¿Qué puedes hacer para evitar este hechizo?',
                'header_counters'                    => 'Contramedidas',
                'header_counters_tooltip'            => 'Habilidades de jugador que pueden hacer fallar este hechizo o cambiar su objetivo.',
                'header_bypasses_immunities'         => 'Omite inmunidad',
                'header_bypasses_immunities_tooltip' => 'Inmunidades de jugador que no detienen este hechizo - se observó que impactaba mientras estaban activas.',
                'header_dispel_type'                 => 'Tipo de disipación',
                'header_dispel_type_tooltip'         => '¿Qué tipo de disipación se puede usar para eliminar este hechizo?',
                'header_mechanic'                    => 'Mecánica',
                'header_cast_time'                   => 'Tiempo de lanzamiento',
                'header_duration'                    => 'Duración',
            ],
            'dungeons' => [
                'title'       => 'Mazmorras',
                'empty'       => 'No está vinculado a ninguna mazmorra.',
                'header_name' => 'Nombre',
            ],
            'npcs' => [
                'title'                 => 'Usado por',
                'empty'                 => 'No se han registrado NPCs.',
                'header_name'           => 'Nombre',
                'header_classification' => 'Clasificación',
                'header_dungeons'       => 'Mazmorras',
            ],
            'event_feed' => [
                'title' => 'Actividad reciente',
                'empty' => 'Todavía no se ha registrado actividad.',
            ],
        ],
    ],
    'activity' => [
        'index' => [
            'title'  => 'Actividad del Compendio',
            'header' => 'Actividad del Compendio',
            'empty'  => 'Todavía no se ha registrado actividad.',
        ],
        'day' => [
            'title'  => ':date - Actividad del Compendio',
            'header' => 'Actividad del Compendio para :date',
            'empty'  => 'No se registró actividad para este día.',
        ],
    ],
    'class' => [
        'index' => [
            'title'  => 'Compendio - Por clase',
            'header' => 'Por clase',
        ],
        'show' => [
            'title'                       => ':name - Por clase',
            'table_header_spell'          => 'Hechizo',
            'table_header_characteristic' => 'Característica',
            'table_header_npcs'           => 'NPCs destacados',
            'no_spells'                   => 'No se encontraron hechizos de control de masas para esta clase en esta versión del juego.',
            'no_npcs'                     => '-',
            'npcs_no_effect'              => 'Inmune',
            'npcs_works_on'               => 'Funciona en',
            'npcs_no_exceptions'          => 'Nada inesperado',
            'npcs_no_data'                => 'Sin datos',
            'npcs_description'            => 'Solo se muestran las sorpresas - trash que resistió, y jefes en los que sí funcionó. Todo lo que se comporta como ya esperas queda excluido. "Ningún efecto observado" significa que otros controles de masas de esta tabla se han visto funcionando en ese NPC, pero este en concreto nunca: es un indicio, no una inmunidad confirmada.',
            'counters'                    => [
                'title'              => 'Habilidades contrarrestables',
                'racial'             => 'Racial (:race)',
                'no_spells'          => 'No se encontraron hechizos de NPC contrarrestables para esta mazmorra.',
                'table_header_spell' => 'Hechizo',
                'table_header_npcs'  => 'NPCs',
            ],
            'reflect' => [
                'title'              => 'Hechizos reflectables',
                'description'        => 'Hechizos de NPC en esta mazmorra que se ha observado que pueden ser reflejados.',
                'no_spells'          => 'No se encontraron hechizos de NPC reflectables para esta mazmorra.',
                'table_header_spell' => 'Hechizo',
                'table_header_npcs'  => 'NPCs',
            ],
        ],
    ],
];
