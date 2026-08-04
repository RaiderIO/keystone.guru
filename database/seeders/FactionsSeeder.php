<?php

namespace Database\Seeders;

use App\Models\Faction;
use Illuminate\Database\Seeder;

class FactionsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $factions = [
            new Faction([
                'id'    => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                'key'   => Faction::FACTION_UNSPECIFIED,
                'name'  => 'factions.unspecified',
                'color' => 'gray',
            ]),
            new Faction([
                'id'    => Faction::ALL[Faction::FACTION_HORDE],
                'key'   => Faction::FACTION_HORDE,
                'name'  => 'factions.horde',
                'color' => 'red',
            ]),
            new Faction([
                'id'    => Faction::ALL[Faction::FACTION_ALLIANCE],
                'key'   => Faction::FACTION_ALLIANCE,
                'name'  => 'factions.alliance',
                'color' => 'blue',
            ]),
        ];

        foreach ($factions as $faction) {
            /** @var $faction Faction */
            $faction->setTable(DatabaseSeeder::getTempTableName(Faction::class))->save();
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [Faction::class];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
