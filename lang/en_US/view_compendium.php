<?php

return [
    'event' => [
        'characteristic_added'   => 'Affected by :name',
        'characteristic_removed' => 'Unaffected by :name',
        'spell_assigned'         => 'Casts :name',
        'spell_created'          => ':spell added to database',
        'property_changed'       => 'Affected by :property',
        'property_removed'       => 'Unaffected by :property',
        'property'               => [
            'aura'   => 'Aura',
            'debuff' => 'Debuff',
        ],
    ],
    'npc' => [
        'index' => [
            'title'                 => 'NPC Compendium',
            'header'                => 'NPC Compendium',
            'boss'                  => 'Boss',
            'table_header_name'     => 'Name',
            'table_header_dungeons' => 'Dungeons',
            'table_header_spells'   => 'Spells',
        ],
        'show' => [
            'title' => ':name — NPC Compendium',
        ],
        'sections' => [
            'header' => [
                'level' => 'Level',
            ],
            'characteristics' => [
                'title'   => 'Characteristics',
                'tooltip' => 'What is this NPC affected by?',
            ],
            'spells' => [
                'title'                      => 'Spells',
                'empty'                      => 'No spells recorded.',
                'header_name'                => 'Name',
                'header_schools'             => 'Schools',
                'header_schools_tooltip'     => 'What type of damage does this spell do?',
                'header_miss_types'          => 'Miss types',
                'header_miss_types_tooltip'  => 'What can you do to avoid this spell?',
                'header_dispel_type'         => 'Dispel type',
                'header_dispel_type_tooltip' => 'What type of dispel can be used to remove this spell?',
                'header_mechanic'            => 'Mechanic',
                'header_cast_time'           => 'Cast time',
                'header_duration'            => 'Duration',
            ],
            'event_feed' => [
                'title' => 'Recent Activity',
                'empty' => 'No activity recorded yet.',
            ],
        ],
    ],
    'activity' => [
        'index' => [
            'title'  => 'Compendium Activity',
            'header' => 'Compendium Activity',
            'empty'  => 'No activity recorded yet.',
        ],
        'day' => [
            'title'  => ':date - Compendium Activity',
            'header' => 'Compendium Activity for :date',
            'empty'  => 'No activity recorded for this day.',
        ],
    ],
];
