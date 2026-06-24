<?php

return [
    'event' => [
        'characteristic_added'   => '',
        'characteristic_removed' => '',
        'spell_assigned'         => '',
        'spell_created'          => '',
        'property_changed'       => '',
        'property_removed'       => '',
        'property'               => [
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
            'title' => '',
        ],
        'sections' => [
            'header' => [
                'level' => '',
            ],
            'characteristics' => [
                'title'   => '',
                'tooltip' => '',
            ],
            'spells' => [
                'title'                      => '',
                'empty'                      => '',
                'header_name'                => '',
                'header_schools'             => '',
                'header_schools_tooltip'     => '',
                'header_miss_types'          => '',
                'header_miss_types_tooltip'  => '',
                'header_dispel_type'         => '',
                'header_dispel_type_tooltip' => '',
                'header_mechanic'            => '',
                'header_cast_time'           => '',
                'header_duration'            => '',
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
            'details' => [
                'title'                      => '',
                'header_schools'             => '',
                'header_schools_tooltip'     => '',
                'header_miss_types'          => '',
                'header_miss_types_tooltip'  => '',
                'header_dispel_type'         => '',
                'header_dispel_type_tooltip' => '',
                'header_mechanic'            => '',
                'header_cast_time'           => '',
                'header_duration'            => '',
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
        ],
    ],
];
