<?php

return [
    'index' => [
        'title'       => '',
        'header'      => '',
        'intro'       => '',
        'data_source' => [
            'title'       => '',
            'description' => '',
            'cta'         => '',
        ],
        'how_it_works' => [
            'title'  => '',
            'step_1' => [
                'title'       => '',
                'description' => '',
            ],
            'step_2' => [
                'title'       => '',
                'description' => '',
            ],
            'step_3' => [
                'title'       => '',
                'description' => '',
            ],
        ],
        'cards' => [
            'npc' => [
                'title'        => '',
                'description'  => '',
                'cta'          => '',
                'count_suffix' => '',
            ],
            'spell' => [
                'title'        => '',
                'description'  => '',
                'cta'          => '',
                'count_suffix' => '',
            ],
            'activity' => [
                'title'       => '',
                'description' => '',
                'cta'         => '',
                'subtitle'    => '',
            ],
            'class' => [
                'title'        => '',
                'description'  => '',
                'cta'          => '',
                'count_suffix' => '',
            ],
        ],
    ],
    'event' => [
        'characteristic_added'    => '',
        'characteristic_removed'  => '',
        'spell_assigned'          => '',
        'spell_created'           => '',
        'property_changed'        => '',
        'property_removed'        => '',
        'counter_added'           => '',
        'counter_removed'         => '',
        'school_recorded'         => '',
        'immunity_bypass_added'   => '',
        'immunity_bypass_removed' => '',
        // Subject-less variants: used when the row already leads with the spell link as its
        // subject, so the description does not repeat the spell name
        'spell_created_no_subject'           => '',
        'counter_added_no_subject'           => '',
        'counter_removed_no_subject'         => '',
        'school_recorded_no_subject'         => '',
        'immunity_bypass_added_no_subject'   => '',
        'immunity_bypass_removed_no_subject' => '',
        'count'                              => '',
        'more'                               => '',
        'property'                           => [
            'aura'   => '',
            'debuff' => '',
        ],
    ],
    'npc' => [
        'index' => [
            'title'                 => '',
            'header'                => '',
            'boss'                  => '',
            'table_header_name'     => '',
            'table_header_dungeons' => '',
            'table_header_spells'   => '',
        ],
        'show' => [
            'title'   => '',
            'wowhead' => '',
        ],
        'sections' => [
            'header' => [
                'level' => '',
            ],
            'characteristics' => [
                'title'        => '',
                'tooltip'      => '',
                'empty'        => '',
                'not_observed' => '',
            ],
            'spells' => [
                'title'                              => '',
                'empty'                              => '',
                'header_name'                        => '',
                'header_schools'                     => '',
                'header_schools_tooltip'             => '',
                'header_miss_types'                  => '',
                'header_miss_types_tooltip'          => '',
                'header_counters'                    => '',
                'header_counters_tooltip'            => '',
                'header_bypasses_immunities'         => '',
                'header_bypasses_immunities_tooltip' => '',
                'header_dispel_type'                 => '',
                'header_dispel_type_tooltip'         => '',
                'header_mechanic'                    => '',
                'header_cast_time'                   => '',
                'header_duration'                    => '',
            ],
            'event_feed' => [
                'title' => '',
                'empty' => '',
            ],
        ],
    ],
    'spell' => [
        'index' => [
            'title'                 => '',
            'header'                => '',
            'table_header_name'     => '',
            'table_header_dungeons' => '',
            'table_header_used_by'  => '',
        ],
        'show' => [
            'title'   => '',
            'wowhead' => '',
        ],
        'sections' => [
            'header' => [
                'aura'   => '',
                'debuff' => '',
            ],
            'description' => [
                'title' => '',
            ],
            'details' => [
                'title'                              => '',
                'header_schools'                     => '',
                'header_schools_tooltip'             => '',
                'header_miss_types'                  => '',
                'header_miss_types_tooltip'          => '',
                'header_counters'                    => '',
                'header_counters_tooltip'            => '',
                'header_bypasses_immunities'         => '',
                'header_bypasses_immunities_tooltip' => '',
                'header_dispel_type'                 => '',
                'header_dispel_type_tooltip'         => '',
                'header_mechanic'                    => '',
                'header_cast_time'                   => '',
                'header_duration'                    => '',
            ],
            'dungeons' => [
                'title'       => '',
                'empty'       => '',
                'header_name' => '',
            ],
            'npcs' => [
                'title'                 => '',
                'empty'                 => '',
                'header_name'           => '',
                'header_classification' => '',
                'header_dungeons'       => '',
            ],
            'event_feed' => [
                'title' => '',
                'empty' => '',
            ],
        ],
    ],
    'activity' => [
        'index' => [
            'title'  => '',
            'header' => '',
            'empty'  => '',
        ],
        'day' => [
            'title'  => '',
            'header' => '',
            'empty'  => '',
        ],
    ],
    'class' => [
        'index' => [
            'title'  => '',
            'header' => '',
        ],
        'show' => [
            'title'                       => '',
            'table_header_spell'          => '',
            'table_header_characteristic' => '',
            'table_header_npcs'           => '',
            'no_spells'                   => '',
            'no_npcs'                     => '',
            'npcs_no_effect'              => '',
            'npcs_works_on'               => '',
            'npcs_no_exceptions'          => '',
            'npcs_no_data'                => '',
            'npcs_description'            => '',
            'counters'                    => [
                'title'              => '',
                'racial'             => '',
                'no_spells'          => '',
                'table_header_spell' => '',
                'table_header_npcs'  => '',
            ],
            'reflect' => [
                'title'              => '',
                'description'        => '',
                'no_spells'          => '',
                'table_header_spell' => '',
                'table_header_npcs'  => '',
            ],
        ],
    ],
];
