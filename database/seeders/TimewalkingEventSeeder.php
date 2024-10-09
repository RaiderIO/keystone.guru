<?php

namespace Database\Seeders;

use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingEvent;
use App\SeederHelpers\Traits\FindsAffixes;
use Illuminate\Database\Seeder;

class TimewalkingEventSeeder extends Seeder implements TableSeederInterface
{
    use FindsAffixes;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timewalkingEventsAttributes = [
            //            TimewalkingEvent::TIMEWALKING_EVENT_LEGION => [
            //                'expansion_id'         => Expansion::where('shortname', Expansion::EXPANSION_LEGION)->first()->id,
            //                'name'                 => 'timewalkingevent.legion.name',
            //                'key'                  => TimewalkingEvent::TIMEWALKING_EVENT_LEGION,
            //                'start'                => '2021-12-07 00:00:00',
            //                'start_duration_weeks' => 4,
            //                'week_interval'        => 18,
            //            ],
            //            [
            //                'expansion_id'         => Expansion::where('shortname', Expansion::EXPANSION_BFA)->first()->id,
            //                'name'                 => 'timewalkingevent.bfa.name',
            //                'key'                  => TimewalkingEvent::TIMEWALKING_EVENT_BFA,
            //                'start'                => '2021-12-07 00:00:00',
            //                'start_duration_weeks' => 2,
            //                'week_interval'        => 14,
            //            ],
            //            [
            //                'expansion_id'         => Expansion::where('shortname', Expansion::EXPANSION_SHADOWLANDS)->first()->id,
            //                'name'                 => 'timewalkingevent.shadowlands.name',
            //                'key'                  => TimewalkingEvent::TIMEWALKING_EVENT_SHADOWLANDS,
            //                'start'                => '2021-12-07 00:00:00',
            //                'start_duration_weeks' => 2,
            //                'week_interval'        => 14,
            //            ],
        ];

        TimewalkingEvent::from(DatabaseSeeder::getTempTableName(TimewalkingEvent::class))->insert($timewalkingEventsAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [TimewalkingEvent::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
