<?php

namespace Database\Seeders;

use App\Models\Faction;
use App\Models\File;
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
                'id'           => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                'key'          => Faction::FACTION_UNSPECIFIED,
                'name'         => 'factions.unspecified',
                'icon_file_id' => -1,
                'color'        => 'gray',
            ]),
            new Faction([
                'id'           => Faction::ALL[Faction::FACTION_HORDE],
                'key'          => Faction::FACTION_HORDE,
                'name'         => 'factions.horde',
                'icon_file_id' => -1,
                'color'        => 'red',
            ]),
            new Faction([
                'id'           => Faction::ALL[Faction::FACTION_ALLIANCE],
                'key'          => Faction::FACTION_ALLIANCE,
                'name'         => 'factions.alliance',
                'icon_file_id' => -1,
                'color'        => 'blue',
            ]),
        ];

        foreach ($factions as $faction) {
            /** @var $faction Faction */
            $faction->setTable(DatabaseSeeder::getTempTableName(Faction::class))->save();

            // Translate faction name to English and convert it to lower case
            $iconName          = strtolower(str_replace(' ', '', $faction->key));
            $icon              = new File();
            $icon->model_id    = $faction->id;
            $icon->model_class = get_class($faction);
            $icon->disk        = 'public';
            $icon->path        = sprintf('images/factions/%s.png', $iconName);
            $icon->save();

            $faction->icon_file_id = $icon->id;
            $faction->setTable(DatabaseSeeder::getTempTableName(Faction::class))->save();
        }
    }

    public static function getAffectedModelClasses(): array
    {
        return [Faction::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
