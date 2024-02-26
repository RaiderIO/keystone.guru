<?php

namespace Database\Seeders;

use App\Models\RouteAttribute;
use Illuminate\Database\Seeder;

class RouteAttributesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Adding route attributes');

        $routeAttributeAttributes = [
            [
                'key' => RouteAttribute::ROUTE_ATTRIBUTE_ROGUE_SHROUD_SKIP,
                'name' => sprintf('routeattributes.%s', RouteAttribute::ROUTE_ATTRIBUTE_ROGUE_SHROUD_SKIP),
                'category' => 'class',
            ], [
                'key' => RouteAttribute::ROUTE_ATTRIBUTE_WARLOCK_GATE_SKIP,
                'name' => sprintf('routeattributes.%s', RouteAttribute::ROUTE_ATTRIBUTE_WARLOCK_GATE_SKIP),
                'category' => 'class',
            ], [
                'key' => RouteAttribute::ROUTE_ATTRIBUTE_MAGE_SLOW_FALL_SKIP,
                'name' => sprintf('routeattributes.%s', RouteAttribute::ROUTE_ATTRIBUTE_MAGE_SLOW_FALL_SKIP),
                'category' => 'class',
            ], [
                'key' => RouteAttribute::ROUTE_ATTRIBUTE_INVISIBILITY_POTION_SKIP,
                'name' => sprintf('routeattributes.%s', RouteAttribute::ROUTE_ATTRIBUTE_INVISIBILITY_POTION_SKIP),
                'category' => 'item',
            ], [
                'key' => RouteAttribute::ROUTE_ATTRIBUTE_DEATH_SKIP,
                'name' => sprintf('routeattributes.%s', RouteAttribute::ROUTE_ATTRIBUTE_DEATH_SKIP),
                'category' => 'misc',
            ],
        ];

        RouteAttribute::from(DatabaseSeeder::getTempTableName(RouteAttribute::class))->insert($routeAttributeAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [RouteAttribute::class];
    }
}
