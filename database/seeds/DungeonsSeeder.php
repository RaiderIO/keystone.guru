<?php

use Illuminate\Database\Seeder;

class DungeonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known dungeons');

        $expansions = \App\Models\Expansion::all();
        $legion = $expansions->where('shortname', 'legion')->first();
        $bfa = $expansions->where('shortname', 'bfa')->first();
        $shadowlands = $expansions->where('shortname', 'shadowlands')->first();


        $dungeonsDataLegion = [
            'Arcway'                         => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 392,
                'enemy_forces_required_teeming' => 466,
                'active'                        => false,
                'floors'                        => [
                    'Arcway' => [
                        'index' => 1
                    ]
                ],
                'floor_couplings'               => []
            ], 'Black Rook Hold'             => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'The Ravenscrypt'            => [
                        'index' => 1
                    ],
                    'The Grand Hall'             => [
                        'index' => 2
                    ],
                    'Ravenshold'                 => [
                        'index' => 3
                    ],
                    'The Rook\'s Roost'          => [
                        'index' => 4
                    ],
                    'Lord Ravencrest\'s Chamber' => [
                        'index' => 5
                    ],
                    'The Raven\'s Crown'         => [
                        'index' => 6
                    ],
                ],
                'floor_couplings'               => []
            ], 'Cathedral of Eternal Night'  => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Hall of the Moon'     => [
                        'index' => 1
                    ],
                    'Twilight Grove'       => [
                        'index' => 2
                    ],
                    'The Emerald Archives' => [
                        'index' => 3
                    ],
                    'Path of Illumination' => [
                        'index' => 4
                    ],
                    'Sacristy of Elune'    => [
                        'index' => 5
                    ]
                ],
                'floor_couplings'               => []
            ], 'Court of Stars'              => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Court of Stars' => [
                        'index' => 1
                    ],
                    // Missing two but whatever
                ],
                'floor_couplings'               => []
            ], 'Darkheart Thicket'           => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Darkheart Thicket' => [
                        'index' => 1
                    ]
                ],
                'floor_couplings'               => []
            ], 'Eye of Azshara'              => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Eye of Azshara' => [
                        'index' => 1
                    ]
                ],
                'floor_couplings'               => []
            ], 'Halls of Valor'              => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 115,
                'enemy_forces_required_teeming' => 151,
                'active'                        => false,
                'floors'                        => [
                    // Yes the indices are intended
                    'The High Gate'              => [
                        'index' => 2
                    ],
                    'Fields of the Eternal Hunt' => [
                        'index' => 1
                    ],
                    'Halls of Valor'             => [
                        'index' => 3
                    ]
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'The High Gate',
                        'to'        => 'Fields of the Eternal Hunt',
                        'direction' => 'right'
                    ],
                    [
                        'from'      => 'Fields of the Eternal Hunt',
                        'to'        => 'The High Gate',
                        'direction' => 'up'
                    ],


                    [
                        'from'      => 'The High Gate',
                        'to'        => 'Halls of Valor',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Halls of Valor',
                        'to'        => 'The High Gate',
                        'direction' => 'down'
                    ]
                ]
            ], 'Lower Karazhan'              => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Master\'s Terrace'    => [
                        'index' => 6
                    ],
                    'Opera Hall Balcony'   => [
                        'index' => 5
                    ],
                    'The Guest Chambers'   => [
                        'index' => 4
                    ],
                    'The Banquet Hall'     => [
                        'index' => 3
                    ],
                    'Upper Livery Stables' => [
                        'index' => 2
                    ],
                    'Servant\'s Quarters'  => [
                        'index' => 1
                    ]
                ],
                'floor_couplings'               => []
            ], 'Maw of Souls'                => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Hellmouth Cliffs' => [
                        'index' => 1
                    ],
                    'The Hold'         => [
                        'index' => 2
                    ],
                    'The Naglfar'      => [
                        'index' => 3
                    ]
                ],
                'floor_couplings'               => []
            ], 'Neltharion\'s Lair'          => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Neltharion\'s Lair' => [
                        'index' => 1
                    ],
                ],
                'floor_couplings'               => []
            ], 'Upper Karazhan'              => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Lower Broken Stair'  => [
                        'index' => 7
                    ],
                    'Upper Broken Stair'  => [
                        'index' => 8
                    ],
                    'The Menagerie'       => [
                        'index' => 9
                    ],
                    'Guardian\'s Library' => [
                        'index' => 10
                    ],
                    'Library Floor'       => [
                        'index' => 11
                    ],
                    'Upper Library'       => [
                        'index' => 12
                    ],
                    'Gamesman\'s Hall'    => [
                        'index' => 13
                    ],
                    'Netherspace'         => [
                        'index' => 14
                    ]
                ],
                'floor_couplings'               => []
            ], 'The Seat of the Triumvirate' => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'The Seat of the Triumvirate' => [
                        'index' => 1
                    ],
                ],
                'floor_couplings'               => []
            ], 'Vault of the Wardens'        => [
                'expansion_id'                  => $legion->id,
                'enemy_forces_required'         => 210,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'The Warden\'s Court'   => [
                        'index' => 1
                    ],
                    'Vault of the Wardens'  => [
                        'index' => 2
                    ],
                    'Vault of the Betrayer' => [
                        'index' => 3
                    ],
                ],
                'floor_couplings'               => []
            ],
        ];


        $dungeonsDataBfa = [
            'Atal\'Dazar'                      => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 198,
                'enemy_forces_required_teeming' => 237,
                'active'                        => true,
                'floors'                        => [
                    'Atal\'Dazar'      => [
                        'index' => 1
                    ],
                    'Sacrificial Pits' => [
                        'index' => 2
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Atal\'Dazar',
                        'to'        => 'Sacrificial Pits',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'Sacrificial Pits',
                        'to'        => 'Atal\'Dazar',
                        'direction' => 'up'
                    ]
                ]
            ],
            'Freehold'                         => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 261,
                'enemy_forces_required_teeming' => 313,
                'active'                        => true,
                'floors'                        => [
                    'Freehold' => [
                        'index' => 1
                    ],
                ],
                'floor_couplings'               => []
            ],
            'Kings\' Rest'                     => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 246,
                'enemy_forces_required_teeming' => 286,
                'active'                        => true,
                'floors'                        => [
                    'Kings\' Rest' => [
                        'index' => 1
                    ]
                ],
                'floor_couplings'               => []
            ],
            'Shrine of the Storm'              => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 695,
                'enemy_forces_required_teeming' => 834,
                'active'                        => true,
                'floors'                        => [
                    'Shrine of the Storm' => [
                        'index' => 1
                    ],
                    'Storm\'s End'        => [
                        'index' => 2
                    ]
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Shrine of the Storm',
                        'to'        => 'Storm\'s End',
                        'direction' => 'right'
                    ],
                    [
                        'from'      => 'Storm\'s End',
                        'to'        => 'Shrine of the Storm',
                        'direction' => 'left'
                    ],
                ]
            ],
            'Siege of Boralus'                 => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 319,
                'enemy_forces_required_teeming' => 383,
                'active'                        => true,
                'floors'                        => [
                    'Siege of Boralus' => [
                        'index' => 1
                    ]
                ],
                'floor_couplings'               => []
            ],
            'Temple of Sethraliss'             => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 220,
                'enemy_forces_required_teeming' => 264,
                'active'                        => true,
                'floors'                        => [
                    'Temple of Sethraliss'  => [
                        'index' => 1
                    ],
                    'Atrium of the Wardens' => [
                        'index' => 2
                    ]
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Temple of Sethraliss',
                        'to'        => 'Atrium of the Wardens',
                        'direction' => 'left'
                    ],
                    [
                        'from'      => 'Atrium of the Wardens',
                        'to'        => 'Temple of Sethraliss',
                        'direction' => 'right'
                    ],
                ]
            ],
            'The MOTHERLODE!!'                 => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 384,
                'enemy_forces_required_teeming' => 460,
                'active'                        => true,
                'floors'                        => [
                    'The Motherlode' => [
                        'index' => 1
                    ]
                ],
                'floor_couplings'               => []
            ],
            'The Underrot'                     => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 252,
                'enemy_forces_required_teeming' => 286,
                'active'                        => true,
                'floors'                        => [
                    'The Underrot'    => [
                        'index' => 1
                    ],
                    'Ruin\'s Descent' => [
                        'index' => 2
                    ]
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'The Underrot',
                        'to'        => 'Ruin\'s Descent',
                        'direction' => 'left'
                    ],
                    [
                        'from'      => 'Ruin\'s Descent',
                        'to'        => 'The Underrot',
                        'direction' => 'up'
                    ]
                ]
            ],
            'Tol Dagor'                        => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 400,
                'enemy_forces_required_teeming' => 479,
                'active'                        => true,
                'floors'                        => [
                    'Tol Dagor'           => [
                        'index' => 1
                    ],
                    'The Drain'           => [
                        'index' => 2
                    ],
                    'The Brig'            => [
                        'index' => 3
                    ],
                    'Detention Block'     => [
                        'index' => 4
                    ],
                    'Officer Quarters'    => [
                        'index' => 5
                    ],
                    'Overseer\'s Redoubt' => [
                        'index' => 6
                    ],
                    'Overseer\'s Summit'  => [
                        'index' => 7
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Tol Dagor',
                        'to'        => 'The Drain',
                        'direction' => 'left'
                    ],
                    [
                        'from'      => 'The Drain',
                        'to'        => 'Tol Dagor',
                        'direction' => 'right'
                    ],


                    [
                        'from'      => 'The Drain',
                        'to'        => 'The Brig',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'The Brig',
                        'to'        => 'The Drain',
                        'direction' => 'down'
                    ],


                    [
                        'from'      => 'The Brig',
                        'to'        => 'Detention Block',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Detention Block',
                        'to'        => 'The Brig',
                        'direction' => 'down'
                    ],


                    [
                        'from'      => 'Detention Block',
                        'to'        => 'Officer Quarters',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Officer Quarters',
                        'to'        => 'Detention Block',
                        'direction' => 'down'
                    ],


                    [
                        'from'      => 'Officer Quarters',
                        'to'        => 'Overseer\'s Redoubt',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Overseer\'s Redoubt',
                        'to'        => 'Officer Quarters',
                        'direction' => 'down'
                    ],


                    [
                        'from'      => 'Overseer\'s Redoubt',
                        'to'        => 'Overseer\'s Summit',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Overseer\'s Summit',
                        'to'        => 'Overseer\'s Redoubt',
                        'direction' => 'down'
                    ],
                ]
            ],
            'Waycrest Manor'                   => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 303,
                'enemy_forces_required_teeming' => 363,
                'active'                        => true,
                'floors'                        => [
                    'The Grand Foyer' => [
                        'index' => 1
                    ],
                    'Upstairs'        => [
                        'index' => 2
                    ],
                    'The Cellar'      => [
                        'index' => 3
                    ],
                    'Catacombs'       => [
                        'index' => 4
                    ],
                    'The Rupture'     => [
                        'index' => 5
                    ]
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'The Grand Foyer',
                        'to'        => 'Upstairs',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Upstairs',
                        'to'        => 'The Grand Foyer',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'The Grand Foyer',
                        'to'        => 'The Cellar',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'The Cellar',
                        'to'        => 'The Grand Foyer',
                        'direction' => 'up'
                    ],


                    [
                        'from'      => 'The Cellar',
                        'to'        => 'Catacombs',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'Catacombs',
                        'to'        => 'The Cellar',
                        'direction' => 'up'
                    ],


                    [
                        'from'      => 'Catacombs',
                        'to'        => 'The Rupture',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'The Rupture',
                        'to'        => 'Catacombs',
                        'direction' => 'up'
                    ],
                ]
            ],
            'Mechagon: Junkyard'               => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 332,
                'enemy_forces_required_teeming' => 398,
                'active'                        => true,
                'floors'                        => [
                    'Mechagon Island' => [
                        'index' => 1
                    ],
                    'Tunnels'         => [
                        'index' => 2
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Mechagon Island',
                        'to'        => 'Tunnels',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'Tunnels',
                        'to'        => 'Mechagon Island',
                        'direction' => 'up'
                    ],
                ]
            ],
            'Mechagon: Workshop'               => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 160,
                'enemy_forces_required_teeming' => 192,
                'active'                        => true,
                'floors'                        => [
                    'The Robodrome'  => [
                        'index' => 1
                    ],
                    // Floor 2 is the waste pipes and don't contain any mobs
                    'The Under Junk' => [
                        'index' => 3
                    ],
                    'Mechagon City'  => [
                        'index' => 4
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'The Robodrome',
                        'to'        => 'The Under Junk',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'The Under Junk',
                        'to'        => 'The Robodrome',
                        'direction' => 'up'
                    ],


                    [
                        'from'      => 'The Under Junk',
                        'to'        => 'Mechagon City',
                        'direction' => 'left'
                    ],
                    [
                        'from'      => 'Mechagon City',
                        'to'        => 'The Under Junk',
                        'direction' => 'right'
                    ],
                ]
            ],
            'Orgrimmar (Horrific Vision)'      => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Orgrimmar' => [
                        'index' => 1
                    ],
                    'The Drag'  => [
                        'index' => 2
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Orgrimmar',
                        'to'        => 'The Drag',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'The Drag',
                        'to'        => 'Orgrimmar',
                        'direction' => 'up'
                    ],
                ]
            ],
            'Stormwind City (Horrific Vision)' => [
                'expansion_id'                  => $bfa->id,
                'enemy_forces_required'         => 0,
                'enemy_forces_required_teeming' => 0,
                'active'                        => false,
                'floors'                        => [
                    'Stormwind City' => [
                        'index' => 1
                    ],
                ],
                'floor_couplings'               => []
            ],
        ];

        $dungeonsDataShadowlands = [
            'De Other Side'         => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'De Other Side' => [
                        'index'   => 1,
                        'default' => true
                    ],
                    'Mechagon'      => [
                        'index' => 2
                    ],
                    'Zul\'Gurub'    => [
                        'index' => 3
                    ],
                    'Ardenweald'    => [
                        'index' => 4
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'De Other Side',
                        'to'        => 'Mechagon',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'Mechagon',
                        'to'        => 'De Other Side',
                        'direction' => 'up'
                    ],

                    [
                        'from'      => 'De Other Side',
                        'to'        => 'Zul\'Gurub',
                        'direction' => 'left'
                    ],
                    [
                        'from'      => 'Zul\'Gurub',
                        'to'        => 'De Other Side',
                        'direction' => 'right'
                    ],

                    [
                        'from'      => 'De Other Side',
                        'to'        => 'Ardenweald',
                        'direction' => 'right'
                    ],
                    [
                        'from'      => 'Ardenweald',
                        'to'        => 'De Other Side',
                        'direction' => 'left'
                    ],
                ]
            ],
            'Halls of Atonement'    => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'Exterior <TEMP>'        => [
                        'index'   => 1,
                        'default' => true
                    ],
                    'Lower Cathedral <TEMP>' => [
                        'index' => 2
                    ],
                    'Upper Cathedral <TEMP>' => [
                        'index' => 3
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Exterior <TEMP>',
                        'to'        => 'Lower Cathedral <TEMP>',
                        'direction' => 'left'
                    ],
                    [
                        'from'      => 'Lower Cathedral <TEMP>',
                        'to'        => 'Exterior <TEMP>',
                        'direction' => 'right'
                    ],

                    [
                        'from'      => 'Lower Cathedral <TEMP>',
                        'to'        => 'Upper Cathedral <TEMP>',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Upper Cathedral <TEMP>',
                        'to'        => 'Lower Cathedral <TEMP>',
                        'direction' => 'down'
                    ],
                ]
            ],
            'Mists of Tirna Scithe' => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'Mists of Tirna Scithe' => [
                        'index'   => 1,
                        'default' => true
                    ],
                ],
                'floor_couplings'               => []
            ],
            'Plaguefall' => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'Plaguefall' => [
                        'index'   => 1,
                        'default' => true
                    ],
                    'The Festering Sanctum' => [
                        'index'   => 2,
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Plaguefall',
                        'to'        => 'The Festering Sanctum',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'The Festering Sanctum',
                        'to'        => 'Plaguefall',
                        'direction' => 'up'
                    ],
                ]
            ],
            'Sanguine Depths' => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'A' => [
                        'index'   => 1,
                        'default' => true
                    ],
                    'B' => [
                        'index'   => 2,
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'A',
                        'to'        => 'B',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'B',
                        'to'        => 'A',
                        'direction' => 'up'
                    ],
                ]
            ],
            'Spires of Ascension' => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'Honor\'s Ascent' => [
                        'index'   => 1,
                    ],
                    'Gardens of Repose' => [
                        'index'   => 2,
                        'default' => true
                    ],
                    'Spire of the Firstborne' => [
                        'index'   => 3,
                    ],
                    'Seat of the Archon' => [
                        'index'   => 4,
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Gardens of Repose',
                        'to'        => 'Honor\'s Ascent',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'Honor\'s Ascent',
                        'to'        => 'Gardens of Repose',
                        'direction' => 'up'
                    ],

                    [
                        'from'      => 'Gardens of Repose',
                        'to'        => 'Spire of the Firstborne',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Spire of the Firstborne',
                        'to'        => 'Gardens of Repose',
                        'direction' => 'down'
                    ],

                    [
                        'from'      => 'Seat of the Archon',
                        'to'        => 'Spire of the Firstborne',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Spire of the Firstborne',
                        'to'        => 'Seat of the Archon',
                        'direction' => 'down'
                    ],
                ]
            ],
            'The Necrotic Wake' => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'The Necrotic Wake' => [
                        'index'   => 1,
                        'default' => true
                    ],
                    'Necrotic Wake A' => [
                        'index'   => 2,
                    ],
                    'Necrotic Wake B' => [
                        'index'   => 3,
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'The Necrotic Wake',
                        'to'        => 'Necrotic Wake A',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Necrotic Wake A',
                        'to'        => 'The Necrotic Wake',
                        'direction' => 'down'
                    ],

                    [
                        'from'      => 'Necrotic Wake A',
                        'to'        => 'Necrotic Wake B',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Necrotic Wake B',
                        'to'        => 'Necrotic Wake A',
                        'direction' => 'down'
                    ],
                ]
            ],
            'Theater of Pain' => [
                'expansion_id'                  => $shadowlands->id,
                'enemy_forces_required'         => -1,
                'enemy_forces_required_teeming' => -1,
                'active'                        => true,
                'floors'                        => [
                    'Theater of Pain' => [
                        'index'   => 1,
                        'default' => true
                    ],
                    'Warlord' => [
                        'index'   => 2,
                    ],
                    'Lich' => [
                        'index'   => 3,
                    ],
                    'Abomination Top' => [
                        'index'   => 4,
                    ],
                    'Abomination Bottom' => [
                        'index'   => 5,
                    ],
                ],
                'floor_couplings'               => [
                    [
                        'from'      => 'Theater of Pain',
                        'to'        => 'Warlord',
                        'direction' => 'right'
                    ],
                    [
                        'from'      => 'Warlord',
                        'to'        => 'Theater of Pain',
                        'direction' => 'left'
                    ],

                    [
                        'from'      => 'Theater of Pain',
                        'to'        => 'Lich',
                        'direction' => 'up'
                    ],
                    [
                        'from'      => 'Lich',
                        'to'        => 'Theater of Pain',
                        'direction' => 'down'
                    ],

                    [
                        'from'      => 'Theater of Pain',
                        'to'        => 'Abomination Top',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'Abomination Top',
                        'to'        => 'Theater of Pain',
                        'direction' => 'up'
                    ],

                    [
                        'from'      => 'Abomination Top',
                        'to'        => 'Abomination Bottom',
                        'direction' => 'down'
                    ],
                    [
                        'from'      => 'Abomination Bottom',
                        'to'        => 'Abomination Top',
                        'direction' => 'up'
                    ],
                ]
            ],
        ];

        $dungeonsData = array_merge($dungeonsDataLegion, $dungeonsDataBfa, $dungeonsDataShadowlands);

        // Add each dungeon
        foreach ($dungeonsData as $name => $dungeonData) {
            $this->command->info('Adding dungeon ' . $name);
            $dungeon = new \App\Models\Dungeon();
            $dungeon->expansion_id = $dungeonData['expansion_id'];
            $dungeon->name = $name;
            $dungeon->enemy_forces_required = $dungeonData['enemy_forces_required'];
            $dungeon->enemy_forces_required_teeming = $dungeonData['enemy_forces_required_teeming'];
            $dungeon->active = $dungeonData['active'];

            $dungeon->save();

            // Add floor
            foreach ($dungeonData['floors'] as $floorName => $floorData) {
                $this->command->info('- Adding floor ' . $floorName);
                $floor = new \App\Models\Floor();
                $floor->dungeon_id = $dungeon->id;
                $floor->name = $floorName;
                $floor->index = $floorData['index'];

                $floor->save();
                // Save the floor back to the array so we can recall it later
                $dungeonData['floors'][$floorName]['floor'] = $floor;
            }

            // Add floor couplings
            foreach ($dungeonData['floor_couplings'] as $connectionData) {
                $this->command->info('-- Adding coupling ' . $connectionData['from'] . ' -> ' . $connectionData['to'] . ' (' . $connectionData['direction'] . ')');
                $coupling = new \App\Models\FloorCoupling();
                $coupling->floor1_id = $dungeonData['floors'][$connectionData['from']]['floor']->id;
                $coupling->floor2_id = $dungeonData['floors'][$connectionData['to']]['floor']->id;
                $coupling->direction = $connectionData['direction'];

                $coupling->save();
            }
        }
    }

    private function _rollback()
    {
        DB::table('dungeons')->truncate();
        DB::table('floors')->truncate();
        DB::table('floor_couplings')->truncate();
    }
}
