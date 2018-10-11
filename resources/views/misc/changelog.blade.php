@extends('layouts.app')

@section('header-title', __('Changelog'))

@section('content')
    <h4>
        2018/10/11 - v2
    </h4>

    <p>
        Map changes:
    <ul>
        <li>
            Fixed where switching maps while having a killzone selected would leave you locked out of selecting new killzones.
        </li>
        <li>
            Interacting with the toolbox while having a killzone selected will now disable the selection on the killzone. This led to multiple issues.
        </li>
    </ul>
    </p>

    <h4>
        2018/10/11
    </h4>
    <p>
        General:
    <ul>
        <li>
            You can now edit your route's title.
        </li>
        <li>
            You can no longer rate your own routes (I saw you rate your own routes a 10/10!).
        </li>
        <li>
            Polished the layout of the website, mainly focussed on mobile users.
        </li>
        <li>
            There's now some feedback on your actions on the website (success/failure of actions).
        </li>
        <li>
            Improved performance of Routes page.
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            Height is now auto-adjusted to your device's viewport rather than a fixed value.
        </li>
    </ul>
    </p>

    <p>
        Mapping changes:
    <ul>
        <li>
            Atal'Dazar
            <ul>
                <li>
                    Shadowblade Stalker near the beginning is now marked as a patrol.
                </li>
                <li>
                    Added a missing pack of Saurids on the way to Priestess Alun'za.
                </li>
                <li>
                    Gilded Priestesses at Priestess Alun'za are no longer linked.
                </li>
            </ul>
            The MOTHERLODE!!
            <ul>
                <li>
                    Stonefuries before Taskmaster Askari are now linked.
                </li>
                <li>
                    Numerous corrections to The D.M.Z. before the last boss (missing bombs, slightly off locations).
                </li>
            </ul>
            The Underrot
            <ul>
                <li>
                    Added a missing pack of 2 patrolling Diseased Lashes between the first and second boss.
                </li>
            </ul>
            Tol Dagor
            <ul>
                <li>
                    Pack of Stringing Swarmers right after the first boss was duplicated (two mobs inside eachother).
                    The duplicated enemies have been removed.
                </li>
            </ul>
        </li>
    </ul>
    </p>


    <h4>
        2018/10/09
    </h4>
    <p>
        General:
    <ul>
        <li class="font-weight-bold">
            Your routes are now unpublished by default. You have to publish them in order to have them show up in the
            search
            and to have other people see your route.
        </li>
        <li>
            Route search can no longer sort on rating as it was broken. I will fix this at a later stage.
        </li>
    </ul>
    </p>
    <p>
        Mapping changes:
    <ul>
        <li>
            Fixed some inconsistencies with Saurids in the middle part of the dungeon.
        </li>
        <li>
            Fixed a host of issues in Shrine of the Storm.
        </li>
        <li>
            Fixed map comments not showing up for logged in users.
        </li>
    </ul>
    </p>


    <h4>
        2018/10/08
    </h4>
    <p>
        General:
    <ul>
        <li>
            You can now delete any route you created from your Profile page.
        </li>
        <li>
            Route rating is now rounded to the nearest two decimal spaces.
        </li>
    </ul>
    </p>
    <p>
        Map changes:
    <ul>
        <li>
            Removed the 'hold ctrl + scroll to zoom' message and functionality. Your mouse wheel is free!
        </li>
    </ul>
    </p>
    <p>
        Bugfixes:
    <ul>
        <li>Raid markers can now again be assigned to enemies for your route.</li>
        <li>Guest viewers of your route can now see the killzones and route you made through the dungeon (view was
            restricted to author of route).
        </li>
        <li>You can no longer select multiple kill zones at once.</li>
    </ul>
    </p>



    <h4>
        2018/10/07 - We're going live!
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            Added a brand new home page!
        </li>
        <li>
            Added Patreon support. Unlockable features include:
            <ul>
                <li>
                    Ad-free website.
                </li>
                <li>
                    Allows the creation of unlisted routes.
                </li>
                <li>
                    Removes the limit on the amount of routes you can create.
                </li>
                <li>
                    More to come at a later date!
                </li>
            </ul>
        </li>
        <li>
            Added try it mode! You can now try the route creator without leaving a trace and without logging in.
        </li>
    </ul>
    <p>
        Route changes:
    <ul>
        <li>
            Fixed an issue where adding enemies to a killzone would cause the raid markers selection to pop up instead.
        </li>
    </ul>
    </p>
    <p>
        Map changes:
    <ul>
        <li>
            Added Teeming for all the dungeons that didn't have data for it yet.
        </li>
    </ul>
    </p>

    <h4>
        2018/09/22
    </h4>
    <p>
        Route changes:
    <ul>
        <li>
            Completely reworked the group selection. You can now select specializations and user experience has
            improved.
        </li>
        <li>
            You can now add raid markers to enemies while constructing your route.
        </li>
    </ul>
    </p>


    <h4>
        2018/09/17
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            The website now has a dark theme by default. More themes/theme switcher are planned, but only after release.
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
            Patrols now show a directional arrow and a dotted line to differentiate between your route and enemy
            patrols.
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