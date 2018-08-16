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
        $this->command->info('Adding known dungeons');

        $expansions = \App\Models\Expansion::all();
        $legion = $expansions->where('shortname', '=', 'legion');
        $bfa = $expansions->where('shortname', '=', 'bfa');



        $dungeons = [
            'Arcway' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Arcway'
                    ]
                ]
            ], 'Black Rook Hold' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'The Ravenscrypt'
                    ],
                    [
                        'index' => 2,
                        'name' => 'The Grand Hall'
                    ],
                    [
                        'index' => 3,
                        'name' => 'Ravenshold'
                    ],
                    [
                        'index' => 4,
                        'name' => 'The Rook\'s Roost'
                    ],
                    [
                        'index' => 5,
                        'name' => 'Lord Ravencrest\'s Chamber'
                    ],
                    [
                        'index' => 6,
                        'name' => 'The Raven\'s Crown'
                    ],
                ]
            ], 'Cathedral of Eternal Night' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Hall of the Moon'
                    ],
                    [
                        'index' => 2,
                        'name' => 'Twilight Grove'
                    ],
                    [
                        'index' => 3,
                        'name' => 'The Emerald Archives'
                    ],
                    [
                        'index' => 4,
                        'name' => 'Path of Illumination'
                    ],
                    [
                        'index' => 5,
                        'name' => 'Sacristy of Elune'
                    ]
                ]
            ], 'Court of Stars' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Court of Stars'
                    ],
                    // Missing two but whatever
                ]
            ], 'Darkheart Thicket' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Darkheart Thicket'
                    ]
                ]
            ], 'Eye of Azshara' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Eye of Azshara'
                    ]
                ]
            ], 'Halls of Valor' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    // Yes the indices are intended
                    [
                        'index' => 2,
                        'name' => 'The High Gate'
                    ],
                    [
                        'index' => 1,
                        'name' => 'Fields of the Eternal Hunt'
                    ],
                    [
                        'index' => 3,
                        'name' => 'Halls of Valor'
                    ]
                ]
            ], 'Lower Karazhan' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 6,
                        'name' => 'Master\'s Terrace'
                    ],
                    [
                        'index' => 5,
                        'name' => 'Opera Hall Balcony'
                    ],
                    [
                        'index' => 4,
                        'name' => 'The Guest Chambers'
                    ],
                    [
                        'index' => 3,
                        'name' => 'The Banquet Hall'
                    ],
                    [
                        'index' => 2,
                        'name' => 'Upper Livery Stables'
                    ],
                    [
                        'index' => 1,
                        'name' => 'Servant\'s Quarters'
                    ]
                ]
            ], 'Maw of Souls' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Hellmouth Cliffs'
                    ],
                    [
                        'index' => 2,
                        'name' => 'The Hold'
                    ],
                    [
                        'index' => 3,
                        'name' => 'The Naglfar'
                    ]
                ]
            ], 'Neltharion\'s Lair' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Neltharion\'s Lair'
                    ],
                ]
            ], 'Upper Karazhan' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 7,
                        'name' => 'Lower Broken Stair'
                    ],
                    [
                        'index' => 8,
                        'name' => 'Upper Broken Stair'
                    ],
                    [
                        'index' => 9,
                        'name' => 'The Menagerie'
                    ],
                    [
                        'index' => 10,
                        'name' => 'Guardian\'s Library'
                    ],
                    [
                        'index' => 11,
                        'name' => 'Library Floor'
                    ],
                    [
                        'index' => 12,
                        'name' => 'Upper Library'
                    ],
                    [
                        'index' => 13,
                        'name' => 'Gamesman\'s Hall'
                    ],
                    [
                        'index' => 14,
                        'name' => 'Netherspace'
                    ]
                ]
            ], 'The Seat of the Triumvirate' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'The Seat of the Triumvirate'
                    ],
                ]
            ], 'Vault of the Wardens' => [
                'expansion_id' => $legion->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'The Warden\'s Court'
                    ],
                    [
                        'index' => 2,
                        'name' => 'Vault of the Wardens'
                    ],
                    [
                        'index' => 3,
                        'name' => 'Vault of the Betrayer'
                    ],
                ]
            ],








            'Atal\'Dazar' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Atal\'Dazar'
                    ],
                    [
                        'index' => 2,
                        'name' => 'Sacrificial Pits'
                    ],
                ]
            ],
            'Freehold' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Freehold'
                    ],
                ]
            ],
            'King\'s Rest' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Kings\' Rest'
                    ]
                ]
            ],
            'Shrine of the Storm' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Shrine of the Storm'
                    ],
                    [
                        'index' => 2,
                        'name' => 'Storm\'s End'
                    ]
                ]
            ],
            'Siege of Boralus' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Siege of Boralus'
                    ]
                ]
            ],
            'Temple of Sethraliss' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Temple of Sethraliss'
                    ],
                    [
                        'index' => 2,
                        'name' => 'Atrium of the Wardens'
                    ]
                ]
            ],
            'The MOTHERLODE!!' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'The Motherlode'
                    ]
                ]
            ],
            'The Underrot' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'The Underrot'
                    ],
                    [
                        'index' => 2,
                        'name' => 'Ruin\'s Descent'
                    ]
                ]
            ],
            'Tol Dagor' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 1,
                        'name' => 'Tol Dagor'
                    ],
                    [
                        'index' => 2,
                        'name' => 'The Drain'
                    ],
                    [
                        'index' => 3,
                        'name' => 'The Brig'
                    ],
                    [
                        'index' => 4,
                        'name' => 'Detention Block'
                    ],
                    [
                        'index' => 5,
                        'name' => 'Officer Quarters'
                    ],
                    [
                        'index' => 6,
                        'name' => 'Overseer\'s Redoubt'
                    ],
                    [
                        'index' => 7,
                        'name' => 'Overseer\'s SUmmit'
                    ],
                ]
            ],
            'Waycrest Manor' => [
                'expansion_id' => $bfa->id,
                'floors' => [
                    [
                        'index' => 2,
                        'name' => 'The Grand Foyer'
                    ],
                    [
                        'index' => 1,
                        'name' => 'Upstairs'
                    ],
                    [
                        'index' => 3,
                        'name' => 'The Cellar'
                    ],
                    [
                        'index' => 4,
                        'name' => 'Catacombs'
                    ],
                    [
                        'index' => 5,
                        'name' => 'The Rupture'
                    ]
                ]
            ],
        ];



    }

    private function _rollback()
    {
        DB::table('dungeons')->truncate();
        DB::table('floors')->truncate();
        DB::table('floor_couplings')->truncate();
    }
}
