<?php

namespace Database\Seeders;

use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingEvent;
use Database\Seeders\Traits\FindsAffixes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimewalkingEventSeeder extends Seeder
{
    use FindsAffixes;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();

        $this->command->info('Adding known timewalking events');

        $timewalkingEventsData = [
            TimewalkingEvent::TIMEWALKING_EVENT_LEGION => [
                'expansion_id'         => Expansion::where('shortname', Expansion::EXPANSION_LEGION)->first()->id,
                'name'                 => 'timewalkingevent.legion.name',
                'key'                  => TimewalkingEvent::TIMEWALKING_EVENT_LEGION,
                'start'                => '2021-12-07 00:00:00',
                'start_duration_weeks' => 4,
                'week_interval'        => 18,
            ],
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

        foreach ($timewalkingEventsData as $timewalkingEvent) {
            TimewalkingEvent::create($timewalkingEvent);
        }
    }

    /**
     *
     */
    private function rollback()
    {
        DB::table('timewalking_events')->truncate();
    }
}
