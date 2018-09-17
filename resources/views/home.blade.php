@extends('layouts.app')

@section('header-title', __('Welcome to keystone.guru!'))

@section('content')
    <p>
        This is a website where players of World of Warcraft can plan their Mythic+ keystone runs in detail. The site is
        currently under construction,
        there may be functions missing, broken, or otherwise incomplete. Please check back later if you want to see the
        finished product!

        <br><br>
        Registering is optional, as an anonymous user you can view routes freely. Please check it out!

        <br><br>
        For feedback, please hop on the <a href="https://discord.gg/2KtWrqw"> <i class="fab fa-discord"></i> Discord
        </a>,
        visit <a
                href="https://www.reddit.com/r/CompetitiveWoW/comments/9dfrlt/feedbackhelp_wanted_for_m_route_planning_website/">
            <i class="fab fa-reddit"></i> Reddit</a>
        or send an e-mail to feedback@keystone.guru!
    </p>

    <h2>{{ __('Enemy forces mapping progress') }}</h2>
    @foreach(\App\Models\Dungeon::active()->get() as $dungeon )
        <div class="row">
            <div class="col-lg-2">
                {{ $dungeon->name }}
            </div>
            <div class="col-lg-10">
                <div class="progress">
                    @php($percent = $dungeon->enemy_forces_mapped_status['percent'])
                    @php($total = $dungeon->enemy_forces_mapped_status['total'])
                    @php($curr = $total - $dungeon->enemy_forces_mapped_status['unmapped'])
                    <div class="progress-bar" style="width: {{ $percent }}%;" role="progressbar"
                         aria-valuenow="{{ $percent }}" aria-valuemin="0"
                         aria-valuemax="100">
                        {{ sprintf('%s/%s %d%%', $curr, $total, $percent) }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <p>
        Enemies whose enemy forces have not been mapped are colored pink on the map. Do you know how many enemy forces
        these enemies give? Please contact me!
    </p>

    <br>
    <h2>Changelog</h2>
    <h4>
        2018/09/17
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            The website now has a dark theme by default. More themes/theme switcher are planned, but only after release
        </li>
        <li>
            A lot of changes to enemies and the enemy forces they give upon death. All dungeons have been mapped, but a
            handful of enemies don't have their enemy forces yet. Mostly these are enemies that are away from the beaten
            path and generally aren't killed.
        </li>
    </ul>

    Route changes:
    <ul>
        <li>
            When you have selected Siege of Boralus, you are now required to enter a faction. This is because Siege of
            Boralus is different for Horde and Alliance.
        </li>
    </ul>

    Map changes:
    <ul>
        <li>
            Patrols now show a directional arrow and a dotted line to differentiate between your route and enemy patrols.
        </li>
    </ul>
    </p>

    <h4>
        2018/09/09
    </h4>

    <p>
        General changes:
    <ul>
        <li>
            The amount of enemy forces that have been assigned to NPCs are now (temporarily) shown when viewing/editing
            your route.
            Not all enemies have their enemy forces added yet, this is a manual job that takes time to process and will
            be added
            over the the coming days/weeks. If the percentage is at 0%, the enemy forces counter on the map will not
            work. If it's
            below 100%, it may not work properly.
        </li>
        <li>
            The Halls of Valor Demo route is now fully functional, though not completely done yet, it depicts a proper
            run.
        </li>
    </ul>

    Route changes:
    <ul>
        <li>
            You now have to specify whether your route will be for Teeming week or not. You can then select either all
            possible Teeming affixes or all non-Teeming affixes based on your selection (optional).
        </li>
        <li>
            Goblins can now be Warlocks, and can no longer be Monks (thanks /u/Caderit).
        </li>
    </ul>

    Map changes:
    <ul>
        <li>
            Enemy forces counter now works (if enemy forces are available for enemies).
        </li>
        <li>
            Added mouse over tooltip on enemies to display their details.
        </li>
        <li>
            Fixed multiple issues related to switching dungeon floors (killzone lines not being removed etc.)
        </li>
    </ul>
    </p>

@endsection