<?php

namespace Database\Seeders;

use App\Models\Affix;
use App\Models\Expansion;
use App\Models\Timewalking\TimewalkingAffixGroupCoupling;
use App\Models\Timewalking\TimewalkingEvent;
use App\Models\Timewalking\TimewalkingEventAffixGroup;
use App\Models\Timewalking\TimewalkingEventAffixGroupCoupling;
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
        $this->_rollback();

        $this->command->info('Adding known timewalking events');

        $timewalkingEventsData = [
            TimewalkingEvent::TIMEWALKING_EVENT_LEGION => [
                'expansion_id'         => Expansion::where('shortname', Expansion::EXPANSION_LEGION)->first()->id,
                'name'                 => 'timewalkingevent.legion.name',
                'key'                  => TimewalkingEvent::TIMEWALKING_EVENT_LEGION,
                'start'                => '2021-12-07 00:00:00',
                'start_duration_weeks' => 2,
                'week_interval'        => 14,
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

        $timewalkingEvents = [];
        foreach ($timewalkingEventsData as $timewalkingEvent) {
            $timewalkingEvents[TimewalkingEvent::TIMEWALKING_EVENT_LEGION] = TimewalkingEvent::create($timewalkingEvent);
        }

        $groups = [
            [
                'timewalking_event_id' => $timewalkingEvents[TimewalkingEvent::TIMEWALKING_EVENT_LEGION]->id,
                'affixes'              => [Affix::AFFIX_TYRANNICAL, Affix::AFFIX_BURSTING, Affix::AFFIX_VOLCANIC, Affix::AFFIX_INFERNAL],
            ],
            [
                'timewalking_event_id' => $timewalkingEvents[TimewalkingEvent::TIMEWALKING_EVENT_LEGION]->id,
                'affixes'              => [Affix::AFFIX_UNKNOWN, Affix::AFFIX_UNKNOWN, Affix::AFFIX_UNKNOWN, Affix::AFFIX_INFERNAL],
            ],
        ];

        $affixes = Affix::all();
        foreach ($groups as $groupArr) {
            $group = TimewalkingEventAffixGroup::create([
                'timewalking_event_id' => $groupArr['timewalking_event_id'],
                'seasonal_index'       => $groupArr['seasonal_index'] ?? null,
            ]);

            foreach ($groupArr['affixes'] as $affixName) {
                $affix = $this->findAffix($affixes, $affixName);

                TimewalkingEventAffixGroupCoupling::create([
                    'affix_id'                         => $affix->id,
                    'timewalking_event_affix_group_id' => $group->id,
                ]);
            }
        }
    }

    /**
     *
     */
    private function _rollback()
    {
        DB::table('timewalking_events')->truncate();
        DB::table('timewalking_event_affix_groups')->truncate();
        DB::table('timewalking_event_affix_group_couplings')->truncate();
    }
}
