<?php

return [
    'index' => [
        'title'       => 'Compendium',
        'header'      => 'Compendium',
        'intro'       => 'The Compendium is a community-powered encyclopedia of all dungeons in the current season of the game. Look up exactly what each NPC does, which spells they cast, how to counter them, and which crowd control works on them.',
        'data_source' => [
            'title'       => 'Always up-to-date',
            'description' => 'The Compendium is kept real-time by combat logs that players upload automatically through the Raider.IO client. Every tracked run quietly improves the data for everyone.',
            'cta'         => 'Install the Raider.IO client',
        ],
        'how_it_works' => [
            'title'  => 'How it works',
            'step_1' => [
                'title'       => 'Pick a section',
                'description' => 'Browse NPCs, Spells, recent Activity, or jump straight to crowd control by class.',
            ],
            'step_2' => [
                'title'       => 'Search & filter',
                'description' => 'Filter by dungeon to focus on exactly the pulls you are preparing for.',
            ],
            'step_3' => [
                'title'       => 'Drill into details',
                'description' => 'Open any NPC or spell to see schools, dispel types, mechanics, durations and more.',
            ],
        ],
        'cards' => [
            'npc' => [
                'title'        => 'NPCs',
                'description'  => 'Every NPC in the game with their abilities, health, classification and the dungeons they appear in.',
                'cta'          => 'Browse NPCs',
                'count_suffix' => 'NPCs catalogued',
            ],
            'spell' => [
                'title'        => 'Spells',
                'description'  => 'Look up any spell to see what it does, how to avoid it, and which NPCs cast it.',
                'cta'          => 'Browse spells',
                'count_suffix' => 'spells catalogued',
            ],
            'activity' => [
                'title'       => 'Activity',
                'description' => 'A live feed of the newest data flowing in from the community, organized by day.',
                'cta'         => 'View activity',
                'subtitle'    => 'Updated daily',
            ],
            'class' => [
                'title'        => 'By Class',
                'description'  => 'See which of your crowd control spells work on which NPCs, grouped by class.',
                'cta'          => 'Browse by class',
                'count_suffix' => 'classes covered',
            ],
        ],
    ],
    'event' => [
        'characteristic_added'    => 'Affected by :name',
        'characteristic_removed'  => 'Unaffected by :name',
        'spell_assigned'          => 'Casts :name',
        'spell_created'           => ':spell added to database',
        'property_changed'        => 'Affected by :property',
        'property_removed'        => 'Unaffected by :property',
        'counter_added'           => ':spell can now be countered by :property',
        'counter_removed'         => ':spell can no longer be countered by :property',
        'school_recorded'         => ':spell deals :schools damage',
        'immunity_bypass_added'   => ':spell was observed landing through :property',
        'immunity_bypass_removed' => ':spell was no longer observed landing through :property',
        // Subject-less variants: used when the row already leads with the spell link as its
        // subject, so the description does not repeat the spell name
        'spell_created_no_subject'           => 'Added to database',
        'counter_added_no_subject'           => 'Can now be countered by :property',
        'counter_removed_no_subject'         => 'Can no longer be countered by :property',
        'school_recorded_no_subject'         => 'Deals :schools damage',
        'immunity_bypass_added_no_subject'   => 'Observed landing through :property',
        'immunity_bypass_removed_no_subject' => 'No longer observed landing through :property',
        'count'                              => ':count event|:count events',
        'more'                               => 'and :count more',
        'property'                           => [
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
            'title'   => ':name - NPC Compendium',
            'wowhead' => 'View on Wowhead',
        ],
        'sections' => [
            'header' => [
                'level' => 'Level',
            ],
            'characteristics' => [
                'title'        => 'Characteristics',
                'tooltip'      => 'What is this NPC affected by?',
                'empty'        => 'No characteristics recorded.',
                'not_observed' => 'Not observed:',
            ],
            'spells' => [
                'title'                              => 'Spells',
                'empty'                              => 'No spells recorded.',
                'header_name'                        => 'Name',
                'header_schools'                     => 'Schools',
                'header_schools_tooltip'             => 'What type of damage does this spell do?',
                'header_miss_types'                  => 'Miss types',
                'header_miss_types_tooltip'          => 'What can you do to avoid this spell?',
                'header_counters'                    => 'Counters',
                'header_counters_tooltip'            => 'Player abilities that can make this spell fizzle or retarget.',
                'header_bypasses_immunities'         => 'Bypasses immunity',
                'header_bypasses_immunities_tooltip' => 'Player immunities that do not stop this spell - it was observed landing while they were active.',
                'header_dispel_type'                 => 'Dispel type',
                'header_dispel_type_tooltip'         => 'What type of dispel can be used to remove this spell?',
                'header_mechanic'                    => 'Mechanic',
                'header_cast_time'                   => 'Cast time',
                'header_duration'                    => 'Duration',
            ],
            'event_feed' => [
                'title' => 'Recent Activity',
                'empty' => 'No activity recorded yet.',
            ],
        ],
    ],
    'spell' => [
        'index' => [
            'title'                 => 'Spell Compendium',
            'header'                => 'Spell Compendium',
            'table_header_name'     => 'Name',
            'table_header_dungeons' => 'Dungeons',
            'table_header_used_by'  => 'Used by',
        ],
        'show' => [
            'title'   => ':name - Spell Compendium',
            'wowhead' => 'View on Wowhead',
        ],
        'sections' => [
            'header' => [
                'aura'   => 'Aura',
                'debuff' => 'Debuff',
            ],
            'description' => [
                'title' => 'Description',
            ],
            'details' => [
                'title'                              => 'Details',
                'header_schools'                     => 'Schools',
                'header_schools_tooltip'             => 'What type of damage does this spell do?',
                'header_miss_types'                  => 'Miss types',
                'header_miss_types_tooltip'          => 'What can you do to avoid this spell?',
                'header_counters'                    => 'Counters',
                'header_counters_tooltip'            => 'Player abilities that can make this spell fizzle or retarget.',
                'header_bypasses_immunities'         => 'Bypasses immunity',
                'header_bypasses_immunities_tooltip' => 'Player immunities that do not stop this spell - it was observed landing while they were active.',
                'header_dispel_type'                 => 'Dispel type',
                'header_dispel_type_tooltip'         => 'What type of dispel can be used to remove this spell?',
                'header_mechanic'                    => 'Mechanic',
                'header_cast_time'                   => 'Cast time',
                'header_duration'                    => 'Duration',
            ],
            'dungeons' => [
                'title'       => 'Dungeons',
                'empty'       => 'Not linked to any dungeons.',
                'header_name' => 'Name',
            ],
            'npcs' => [
                'title'                 => 'Used by',
                'empty'                 => 'No NPCs recorded.',
                'header_name'           => 'Name',
                'header_classification' => 'Classification',
                'header_dungeons'       => 'Dungeons',
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
    'class' => [
        'index' => [
            'title'  => 'Compendium - By Class',
            'header' => 'By Class',
        ],
        'show' => [
            'title'                       => ':name - By Class',
            'table_header_spell'          => 'Spell',
            'table_header_characteristic' => 'Characteristic',
            'table_header_npcs'           => 'NPCs',
            'no_spells'                   => 'No CC spells found for this class in this game version.',
            'no_npcs'                     => '-',
            'npcs_affected'               => 'Affected',
            'npcs_unaffected'             => 'Not affected',
            'npcs_description'            => 'Each row lists the affected NPCs, or the unaffected ones where that is the shorter list. An NPC is only ever listed as not affected if another crowd control effect from this table has been observed landing on it - a taunt does not count.',
            'counters'                    => [
                'title'              => 'Counterable abilities',
                'racial'             => 'Racial (:race)',
                'no_spells'          => 'No counterable NPC spells found for this dungeon.',
                'table_header_spell' => 'Spell',
                'table_header_npcs'  => 'NPCs',
            ],
            'reflect' => [
                'title'              => 'Reflectable spells',
                'description'        => 'NPC spells in this dungeon that have been observed being reflected.',
                'no_spells'          => 'No reflectable NPC spells found for this dungeon.',
                'table_header_spell' => 'Spell',
                'table_header_npcs'  => 'NPCs',
            ],
        ],
    ],
];
