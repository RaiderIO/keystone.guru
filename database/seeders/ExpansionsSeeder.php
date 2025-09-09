<?php

namespace Database\Seeders;

use App\Models\Expansion;
use App\Models\File;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ExpansionsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expansions = [
            'expansions.legion.name'   => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_LEGION,
                'color'       => '#27ff0f',
                'released_at' => Carbon::make('2016-08-30 00:00:00'),
            ]),
            'expansions.bfa.name'      => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_BFA,
                'color'       => '#906554',
                'released_at' => Carbon::make('2018-08-14 00:00:00'),
            ]),
            'expansions.sl.name'       => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_SHADOWLANDS,
                'color'       => '#5832a8',
                'released_at' => Carbon::make('2020-11-24 00:00:00'),
            ]),
            'expansions.classic.name'  => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_CLASSIC,
                'color'       => '#ebbd34',
                'released_at' => Carbon::make('2004-11-23 00:00:00'),
            ]),
            'expansions.tbc.name'      => new Expansion([
                'active'      => 0,
                'shortname'   => Expansion::EXPANSION_TBC,
                'color'       => '#198033',
                'released_at' => Carbon::make('2007-01-16 00:00:00'),
            ]),
            'expansions.wotlk.name'    => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_WOTLK,
                'color'       => '#11dff2',
                'released_at' => Carbon::make('2008-11-13 00:00:00'),
            ]),
            'expansions.cata.name'     => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_CATACLYSM,
                'color'       => '#bf5006',
                'released_at' => Carbon::make('2010-12-07 00:00:00'),
            ]),
            'expansions.mop.name'      => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_MOP,
                'color'       => '#34bf06',
                'released_at' => Carbon::make('2012-09-25 00:00:00'),
            ]),
            'expansions.wod.name'      => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_WOD,
                'color'       => '#875f03',
                'released_at' => Carbon::make('2014-11-13 00:00:00'),
            ]),
            'expansions.df.name'       => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_DRAGONFLIGHT,
                'color'       => '#b0a497',
                'released_at' => Carbon::make('2022-11-29 00:00:00'),
            ]),
            'expansions.tww.name'      => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_TWW,
                'color'       => '#8B0000',
                'released_at' => Carbon::make('2024-08-26 00:00:00'),
            ]),
            'expansions.midnight.name' => new Expansion([
                'active'      => 0,
                'shortname'   => Expansion::EXPANSION_MIDNIGHT,
                'color'       => '#4B0082',
                'released_at' => Carbon::make('2028-08-26 00:00:00'),
            ]),
            'expansions.tlt.name'      => new Expansion([
                'active'      => 0,
                'shortname'   => Expansion::EXPANSION_TLT,
                'color'       => '#6D6E5C',
                'released_at' => Carbon::make('2029-08-26 00:00:00'),
            ]),
        ];

        foreach ($expansions as $name => $expansion) {
            /** @var Expansion $expansion */
            $expansion->name = $name;
            // Temp file
            $expansion->icon_file_id = -1;
            $expansion->setTable(DatabaseSeeder::getTempTableName(Expansion::class))->save();

            $icon              = new File();
            $icon->model_id    = $expansion->id;
            $icon->model_class = get_class($expansion);
            $icon->disk        = 'public';
            $icon->path        = sprintf('images/expansions/%s.png', $expansion->shortname);
            $icon->save();

            $expansion->icon_file_id = $icon->id;
            $expansion->setTable(DatabaseSeeder::getTempTableName(Expansion::class))->save();
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [Expansion::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
