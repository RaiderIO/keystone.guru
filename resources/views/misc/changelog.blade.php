@extends('layouts.app', ['showLegalModal' => false])

@section('header-title', __('Changelog'))

@section('content')
    <h4>
        2018/11/04
    </h4>

    <p>
        Map changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/40">#40</a> The view and edit pages for routes are
            completely reworked. The map is now full screen, a toggleable sidebar holds all interaction/information.
            Because of the increased screen real-estate for the map, a new zoom level has been added.
        </li>
        <li>
            Fixed an issue where Infested Voting information was not available on the tryout version. Voting is still
            not possible if you're not logged in.
        </li>
        <li>
            Fixed an issue where you could attempt to assign raid markers to enemies in view mode (didn't work, but
            still). I also think I re-introduced this bug so hopefully it stays gone for a while now.
        </li>
        <li>
            Many small changes and improvements to the mapping experience.
        </li>
    </ul>
    </p>
    <h4>
        2018/10/28
    </h4>

    <p>
        General:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/75">#75</a> Profile page is now tabbed; it looks a
            lot better now!
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/29">#29</a> Added privacy controls in your Profile
            for opting out of Google Analytics cookies and disabling Personalized Ads for Google Adsense. With both
            options enabled, you should have no third-party cookies on your browser from Keystone.guru.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/74">#74</a> Now users now have to explicitly agree
            to the privacy policy, terms of service and the cookie policy. Existing users have to give their consent to
            continue using the site.
        </li>
    </ul>
    </p>

    <h4>
        2018/10/27
    </h4>

    <p>
        General:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/68">#68</a> Editing a dungeon with Teeming selected
            will now show the proper valid affixes again rather than just
            non-teeming affixes.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/57">#57</a> Failing validation for a new route will
            no longer reset your selected spec/class/race selections.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/42">#42</a> Adjusted various labels when editing a
            route away from their defaults to more clear new ones.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/69">#69</a> You can now select your region and
            timezone in your profile. This will make the Affixes page 100% accurate to your region + timezone. This was
            also needed to properly implement <a href="https://github.com/Wotuu/keystone.guru/issues/39">#39</a> (see
            below).
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/62">#62</a> Re-wrote all code related to visualising
            enemies on the map. The new setup allows me to extend the system and display a lot more information
            regarding enemies. Look for a new dropdown to the top right of your map to change visualization layers. The
            options are limited for now, but as soon as I gather more information about enemies this list will fill with
            more options. Because of this, raid markers no longer completely replace the selected enemy, but place the
            marker to the top of them instead (just like in-game).
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/39">#39</a> With help of the above, all logged in
            users can now vote on all enemies to be Infested or not. Using the new dropdown for visualization selection
            in the top right you can select a new 'Infested Voting' visualization, which allows you to vote yes or no on
            each enemy on-screen if they're infested or not. Each enemy requires
            {{ config('keystoneguru.infested_user_vote_threshold') }} more yes votes than no votes to be marked
            as Infested on everyone's map. Every reset, the votes are cleared and the voting begins again (Infested
            enemies change every week).
            <br><br>
            To vote for Infested enemies, you need to have your region and timezone set in your profile. Why? As a
            European you can vote for Infested enemies on Tuesday afternoon, while Americas will have a new set of
            Infested enemies already and they're voting for theirs. You don't want your 'last week' votes messing up
            the 'new' votes for the next week that are already being voted for in America.
        </li>
    </ul>
    </p>
    <p>
        Mapping changes:
    <ul>
        <li>
            All dungeons
            <ul>
                <li>
                    Due to Infested voting, all enemies which are tightly clustered have been spaced up slightly as to
                    not impede with the voting process. If this is still an issue I'll consider adding another zoom
                    level to the maps.
                </li>
            </ul>
        </li>
        <li>
            Shrine of the Storm
            <ul>
                <li>
                    Several duplicate enemies on the second floor were removed.
                </li>
            </ul>
        </li>
        <li>
            Siege of Boralus
            <ul>
                <li>
                    Several duplicate enemies between the first and second boss were removed.
                </li>
            </ul>
        </li>
        <li>
            Tol Dagor
            <ul>
                <li>
                    Removed two duplicate enemies in the Overseer's Redoubt.
                </li>
                <li>
                    Added some missing enemies in the Officer Quarters.
                </li>
            </ul>
        </li>
        <li>
            The Underrot
            <ul>
                <li>
                    Re-positioned a lot of enemies to their correct location on the map. The in-game map (and thus the
                    one on the website) isn't very accurate so some enemies may look a bit out of place, but it's the
                    best thing to do. Hopefully Blizzard fixes their map some time so the enemies are correctly placed.
                </li>
                <li>
                    Added flying Feral Bloodswarmers after the second boss.
                </li>
                <li>
                    Added a few missing Grotesque Horrors at the south part after the third boss.
                </li>
            </ul>
        </li>
    </ul>
    </p>

    <h4>
        2018/10/18
    </h4>

    <p>
        General:
    <ul>
        <li>
            Increased performance of the website. I'll continue to monitor the performance as the amount of users &
            routes grow.
        </li>
    </ul>
    </p>
    <p>
        Map changes:
    <ul>
        <li>
            You can no longer attempt to assign raid markers while in view mode.
        </li>
        <li>
            Raid markers can now be unassigned from enemies (in edit mode, of course).
        </li>
    </ul>
    </p>

    <h4>
        2018/10/16
    </h4>

    <p>
        General:
    <ul>
        <li>
            You can now clone your own route or someone else's! You don't need to redo your entire route just to make
            some small changes between affixes. More routes = better!
        </li>
        <li>
            Added password strength hint; increased minimum password length to 8 (but really, do more characters!)
        </li>
        <li>
            Entering incorrect login credentials will now redirect you to a login page. Upon successful login, you are
            now redirected to where you came from.
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            Viewer: clicking a comment will no longer give you a prompt to edit the comment (which won't work even if
            you tried).
        </li>
        <li>
            Fixed enemy forces display in tryout mode.
        </li>
    </ul>
    </p>

    <p>
        Mapping changes:
    <ul>
        <li>
            The Underrot
            <ul>
                <li>
                    Removed an enemy from the pack of 4 just before the third boss which isn't there in-game.
                </li>
                <li>
                    Added a Grotesque Horror that's only there on teeming, removed another that was never there.
                </li>
            </ul>
        </li>
    </ul>
    </p>

    <h4>
        2018/10/13
    </h4>

    <p>
        Map changes:
    <ul>
        <li>
            Corrected Teeming enemy forces needed for The Underrot, King's Rest
        </li>
    </ul>
    </p>

    <h4>
        2018/10/12
    </h4>

    <p>
        General:
    <ul>
        <li>
            Ratings now show as a '-' if no votes have been cast rather than 1 (0 votes).
        </li>
        <li>
            Increased polish all around, mostly minor things.
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            Deleting a killzone with enemies attached will now allow you to re-attach those enemies to another killzone
            again.
        </li>
        <li>
            Switching floors will no longer generate duplicate killzones (also potentially leading to inflated enemy
            forces counts, these are corrected).
        </li>
        <li>
            Fixed multiple other issues related to switching floors.
        </li>
        <li>
            Improved error handling when things go wrong when planning your route.
        </li>
        <li>
            You are now given feedback when deleting objects has succeeded.
        </li>
    </ul>
    </p>

    <h4>
        2018/10/11 - v2
    </h4>

    <p>
        Map changes:
    <ul>
        <li>
            Fixed where switching maps while having a killzone selected would leave you locked out of selecting new
            killzones.
        </li>
        <li>
            Interacting with the toolbox while having a killzone selected will now disable the selection on the
            killzone. This led to multiple issues.
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