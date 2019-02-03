@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Changelog')])

@section('header-title', __('Changelog'))

@section('content')

    <h4>
        v2.0 (2019/02/03)
    </h4>
    <p>
        General changes:

    <ul>
        <li>
            You can now create a new route from a Method Dungeon Tools export string! Keystone.guru will import your
            pulls, affix, free-drawn shapes and notes from your string.
        </li>
    </ul>
    </p>

    <p>
        Map changes:

    <ul>
        <li>
            You can now free draw lines with a color and weight of your choice.
        </li>
        <li>
            Teeming enemies are now displayed with a red border around them to help see what changed from a 'normal'
            week.
        </li>
        <li>
            Newly drawn Routes are no longer half-transparent before a refresh of the map.
        </li>
        <li>
            Currently selected drawing tool is now highlighted for the duration of the drawing.
        </li>
        <li>
            Added color and weight selection to draw controls which affects newly generated Routes, drawn lines and
            Killzones.
        </li>
    </ul>
    </p>
    <p>
        Mapping changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/93">#95</a>
            Due to synchronizing MDT enemies with Keystone.guru enemies, I've had to ensure any differences between MDT
            and Keystone.guru were ironed out. The changes were as follows:
        </li>
        <li>
            Atal'dazar
            <ul>
                <li>
                    Added two missing Dazar'ai Colossi to the south east which were missing on Teeming weeks.
                </li>
                <li>
                    Added missing Reanimation Totem to accompany the Reanimated Honor Guard when going towards the north
                    from the start.
                </li>
                <li>
                    A Feasting Skyscreamer in the middle of the dungeon was incorrectly marked as Alliance only, this
                    has been rectified to be visible for everyone.
                </li>
            </ul>
        </li>
        <li>
            Freehold
            <ul>
                <li>
                    Unmarked an Irontide Bonesaw as Teeming before Skycap'n Kragg.
                </li>
                <li>
                    Added missing Irontide Bonesaw on Teeming week before Skycap'n Kragg.
                </li>
                <li>
                    Added missing pack of an Irontide Enforcer, Crackshot and two Bonesaws after Skycap'n Kragg.
                </li>
                <li>
                    Added missing Blacktooth Scrappers and Blacktooth Brutes right after the first rope bridge, around
                    the center of the square.
                </li>
                <li>
                    Added missing Irontide Crusher on Teeming week before Ring of Booty.
                </li>
                <li>
                    Added various missing Cutwater Knife Jugglers/Harpooner to various packs before Ring of Booty.
                </li>
            </ul>
        </li>
        <li>
            Kings' Rest
            <ul>
                <li>
                    Added missing Shadow-Borne Witch Doctor on Teeming week before The Golden Serpent.
                </li>
                <li>
                    Added missing Shadow-Borne Champion on non-Teeming weeks in The Golden Serpent's room just before
                    going to the next room. Also removed Teeming enemies from that same pack.
                </li>
                <li>
                    Removed various Teeming enemies in the room with the mini bosses.
                </li>
                <li>
                    Removed a Teeming Purification Construct prior to Mchimba the Embalmer.
                </li>
                <li>
                    Added missing Irontide Crusher on Teeming week before Ring of Booty.
                </li>
                <li>
                    Removed a Teeming Spectral Beastmaster prior to The Council of Tribes.
                </li>
                <li>
                    Removed a Teeming Spectral Brute prior to The Council of Tribes.
                </li>
            </ul>
        </li>
        <li>
            Siege of Boralus (Shared)
            <ul>
                <li>
                    Added missing Irontide Marauder around Dread Captain Lockwood.
                </li>
            </ul>
        </li>
        <li>
            Siege of Boralus (Horde)
            <ul>
                <li>
                    Marked a Scrimshaw Gutter and Kul Tiran Footman in the beginning at the end of the dock as
                    Horde-only as was intended.
                </li>
                <li>
                    Adds for Sergeant Bainbridge are now displayed.
                </li>
                <li>
                    Fixed various Scrimshaw Gutters and Kul Tiran Footmen marked as 'any' to Horde right after the
                    broken ship, before Dread Captain Lockwood.
                </li>
                <li>
                    Removed one solo Scrimshaw Gutter right after the broken ship, before Dread Captain Lockwood.
                </li>
                <li>
                    Removed two Teeming Ashvane Spotters in the big pack right after the broken ship, before Dread
                    Captain Lockwood.
                </li>
            </ul>
        </li>
        <li>
            Siege of Boralus (Alliance)
            <ul>
                <li>
                    Added various enemies that exist below the docks at the start.
                </li>
                <li>
                    Added missing pack of two Blacktar Bombers in the initial Docks section.
                </li>
                <li>
                    Added missing pack of five Scrimshaw Gutters and a Scrimshaw Enforcer in the initial Docks section.
                </li>
                <li>
                    Added missing Scrimshaw Gutter in the pack in the corner after the initial Docks section, when going
                    down the stairs.
                </li>
                <li>
                    Added a missing pack of two Snarling Dockhounds at the gallows before Chopper Redhook.
                </li>
                <li>
                    Merged two packs at the gallows before Chopper Redhook; converted two Scrimshaw Gutters to Blacktar
                    Bombers to complete the new pack.
                </li>
                <li>
                    Converted an Irontide Enforcer to an Irontide Raider in one of the first packs on the square with
                    Chopper Redhook.
                </li>
                <li>
                    Added Chopper Redhook and his adds.
                </li>
                <li>
                    Added a pack of seven Irontide Marauders on the docks right before the broken ship.
                </li>
                <li>
                    Added two Irontide Marauders on the docks right after the broken ship.
                </li>
                <li>
                    Added two Irontide Marauder around Dread Captain Lockwood.
                </li>
                <li>
                    Removed a pack of six enemies as the last pack before Viq'Goth.
                </li>
            </ul>
        </li>
        <li>
            Shrine of the Storm
            <ul>
                <li>
                    Removed a Teeming Tidesage Spiritualist that was patrolling on the first set of stairs.
                </li>
                <li>
                    Removed a Teeming Shrine Templar on the pack right before crossing the first bridge (after
                    mini-bosses).
                </li>
                <li>
                    Added missing Abyss Dweller that appears in the room with the two Drowned Depthbringers.
                </li>
                <li>
                    In the same room, added a missing Abyss Dweller on Teeming weeks.
                </li>
                <li>
                    Added missing Irontide Crusher on Teeming week before Ring of Booty.
                </li>
            </ul>
        </li>
        <li>
            Temple of Sethraliss
            <ul>
                <li>
                    Removed two Sandfury Stonefists from the pack when going right from the beginning.
                </li>
                <li>
                    The patrolling Sandfury Stonefist between the second set of packs is now Teeming only.
                </li>
                <li>
                    Updated various Teeming and non-Teeming enemies around the first bosses.
                </li>
                <li>
                    Added missing Teeming pack of Shrouded Fangs and Krolusk Hatchlings in the cave with the Krolusks.
                </li>
                <li>
                    Fixed enemy NPCs of various enemies in the same room.
                </li>
                <li>
                    Removed three Teeming Crazed Incubators around Merektha.
                </li>
                <li>
                    Added two Eyes of Sethraliss which grant 12 enemy forces each upon completing the eye throwing
                    event.
                </li>
            </ul>
        </li>
        <li>
            The MOTHERLODE!!
            <ul>
                <li>
                    Added some missing Venture Co. Longshoreman on the dock in the beginning (though they give 0
                    forces).
                </li>
                <li>
                    Removed a Teeming Addled Thug and Hired Assassin in the pack prior to the first boss.
                </li>
                <li>
                    Added a missing patrolling Venture Co. Mastermind before Rixxa Fluxflame.
                </li>
                <li>
                    Added a Teeming Venture Co. Alchemist in a pack before Rixxa Fluxflame.
                </li>
                <li>
                    Re-did the south pack just before the last pack before Rixxa Fluxflame (get it?).
                </li>
                <li>
                    Removed a Teeming Venture Co. Alchemist and a Weapons Tester in the pack right before Rixxa
                    Fluxflame.
                </li>
                <li>
                    Added a bunch of missing Crawler Mines in The V.M.Z, also updated mines to have correct Teeming
                    appearance.
                </li>
            </ul>
        </li>
        <li>
            The Underrot
            <ul>
                <li>
                    Unmarked two Chosen Matrons on the left pack after the first bridge as Teeming.
                </li>
                <li>
                    The patrol on the right side of the bridge now has two Devout Blood Priests.
                </li>
                <li>
                    Removed two Chosen Matrons the right pack after the first bridge.
                </li>
                <li>
                    Lots of reconfiguration around Cragmaw the Infested. Added various new enemies, removed invalid
                    ones, re-marked and un-marked Teeming enemies.
                </li>
                <li>
                    Unmarked one Reanimated Guardian as Teeming, in the patrolling pack of 5 before Sporecaller Zancha.
                </li>
                <li>
                    Removed the Fallen Deathspeaker + adds that patrols after Sporecaller Zancha.
                </li>
            </ul>
        </li>
        <li>
            Tol Dagor
            <ul>
                <li>
                    Moved the first pack of Stinging Parasites to the The Drain. Your routes are auto-converted; if you
                    killed the pack, it'll now be killed on the second floor instead.
                </li>
                <li>
                    Added two missing packs of Stinging Parasites in The Drain.
                </li>
                <li>
                    Added missing Sewer Vicejaw, removed Teeming Sewer Vicejaw in The Drain.
                </li>
                <li>
                    Added a few missing Despondent Scallywags in The Brig.
                </li>
                <li>
                    Removed a Cutwater Striker from the first pack you encounter when entering the Detention Block,
                    fixed Teeming enemies for said pack as well.
                </li>
                <li>
                    Removed a Teeming Cutwater Striker from the pack with the Block Warden.
                </li>
                <li>
                    Added two missing Teeming Ashvane Marines and an Ashvane Officer in the Officers Quarters on the
                    left side of the area.
                </li>
            </ul>
        </li>
        <li>
            Waycrest Manor
            <ul>
                <li>
                    Corrected an issue where Upstairs was marked as the first floor rather than The Grand Foyer.
                </li>
                <li>
                    Removed middle pack of Soul Essences upon entering the dungeon.
                </li>
                <li>
                    Added a Heartsbane Runeweaver after taking a left initially, then right again.
                </li>
                <li>
                    Unmarked a Heartsbane Runeweaver as Teeming in the room north of the Sister bosses.
                </li>
                <li>
                    Removed Heartsbane Runeweaver in The Hunting Lodge.
                </li>
                <li>
                    Converted a Maddened Survivalist to a Diseased Mastiff in the upper pack in The Hunting Lodge (yes,
                    this must've hurt).
                </li>
                <li>
                    Converted a Maddened Survivalist to a Crazed Marksman in the lower pack in The Hunting Lodge (this
                    didn't really hurt).
                </li>
                <li>
                    Updated a lot of enemies around the Banquet Hall. Most of the packs, in fact.
                </li>
                <li>
                    Upstairs: Converted a Maddened Survivalist to a Crazed Marksman in the pack near the Hunting Lodge.
                </li>
                <li>
                    Removed a Teeming Marked Sister in The Cellar in the pack on the top right.
                </li>
                <li>
                    Removed a Teeming Coven Thornshaper in The Cellar in the pack on the top left.
                </li>
            </ul>
        </li>
    </ul>

    <h4>
        v1.6 (2019/01/04)
    </h4>
    <p>
        Map changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/93">#93</a>
            A killzone and its selected enemies is now displayed as a polygon instead of a target with lines coming out
            of it. This should be prettier way of displaying the information on screen from the way it used to be. You
            can also change the color of the polygon by clicking it after creation and editing its color. Note: be sure
            to use the visibility toggles on the top right to enable/disable any overlapping layers.
        </li>
    </ul>

    <h4>
        v1.5.1 (2018/12/17)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            Fixed default sorting on Routes preview page to properly default to the current week's affixes, not by
            dungeon.
        </li>
        <li>
            Fixed some route thumbnails not being generated.
        </li>
        <li>
            Fixed creating a new route with Attributes assigned from the start giving a 500 server error.
        </li>
    </ul>

    <h4>
        v1.5 (2018/12/14)
    </h4>
    <p>
        Update from me: due to some unfortunate and unexpected circumstances I haven't had much time to work on the
        website the past few weeks. I have slowly started working on the website again though, and I remain dedicated
        to improving Keystone.guru. Please stay tuned!
    </p>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/90">#90</a>
            Introduced a new feature called 'attributes'. A Route can be coupled to one or more attributes which
            describe certain characteristics of a Route. Does the route contain Warlock Demonic Gateway skips, but you
            can't do these because your group does not run with a Warlock? If the author has flagged their Route with
            the appropriate attributes you can now filter out Routes that contain a Warlock Demonic Gateway skip.
            There are currently attributes for Rogue Shroud, Warlock Gate skips, Mage Slow Fall skip (Shrine of the
            Storm), Invisibility Potion and Death skip. If you have more ideas for attributes please let me know and I
            will consider adding them.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/84">#84</a>
            Added a bigger list view option for the Routes page. This allows you to see a preview thumbnail of a Route
            for easy identification if the route suits your needs. You can scroll through all floors using your mouse
            (drag the image) or using touch in touch-enabled devices.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/65">#65</a>
            The Routes page has been updated with new sorting mechanisms for affixes, attributes, author and rating.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/52">#52</a>
            The Route page now shows the views a route has gotten.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/90">#90</a>
            The Affixes page no longer accidentally counted past seasons double, leading to incorrect dates for the
            affixes per week.
        </li>
    </ul>
    </p>

    <h4>
        v1.4.1 (2018/11/08)
    </h4>

    <p>
        General changes:
    <ul>
        <li>
            Added a dedicated page for Infested Voting and a way to summon a dungeon of your choice to do the voting on.
            You can find the page in the top navigation bar (or at https://keystone.guru/infested), it contains
            instructions on how to get started.
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            Selected enemy visualisation will no longer reset to 'aggressiveness' when changing floors. Removed
            'infested voting' option, this is now done through a special page and not through view/editing of routes
            which was confusing and generally not very well done (see above).
        </li>
        <li>
            Fixed Enemy Forces display in Microsoft Edge.
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
                    Re-added Reanimation Totems to specific packs on the north side of the dungeon.
                </li>
            </ul>
        </li>
        <li>
            Freehold
            <ul>
                <li>
                    Added two missing Freehold Mules right after the second boss.
                </li>
                <li>
                    Corrected some enemy NPCs around the Ring of Booty.
                </li>
            </ul>
        </li>
        <li>
            Kings' Rest
            <ul>
                <li>
                    Added three missing Embalming Fluids in the chamber at the second boss.
                </li>
            </ul>
        <li>
            Temple of Sethraliss
            <ul>
                <li>
                    Removed Teeming status from two packs in the beginning; they show up on non-Teeming weeks as well.
                </li>
                <li>
                    Added a missing Shrouded Fang that patrols around the first boss.
                </li>
                <li>
                    Removed a duplicate Scale Krolusk Rider right after the cavern with Mature Krolusks.
                </li>
            </ul>
        </li>
    </ul>
    </p>


    <h4>
        v1.4 (2018/11/05)
    </h4>

    <p>
        General changes:
    <ul>
        <li>
            Added titles for all pages on the website. This will aid multi browser-tab usage.
        </li>
    </ul>
    </p>

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
        v1.3.1 (2018/10/30)
    </h4>

    <p>
        Mapping changes (most thanks to /u/VTSVirus):
    <ul>
        <li>
            Atal'Dazar
            <ul>
                <li>
                    Added a missing pack of 5 before the south boss, when going south from the start. They pop in once
                    you get close, hence me missing those.
                </li>
            </ul>
        </li>
        <li>
            Kings' Rest
            <ul>
                <li>
                    Added Minions of Zul. They do not give trash %-age, but they are a non-trivial enemy so they should
                    be included.
                </li>
                <li>
                    Added Shadow of Zul. Same as above, though he does give some %-age (I never realised due to always
                    being at 100% prior to reaching him).
                </li>
                <li>
                    Spectral Brute now patrols the entire hallway before the third boss.
                </li>
            </ul>
        </li>
        <li>
            Shrine of the Storm
            <ul>
                <li>
                    Added to Template Attendants on the far right before the first boss.
                </li>
                <li>
                    Corrected patrol of Tidesage Enforcer after the second boss (prior to the room with tentacles).
                </li>
            </ul>
        </li>
        <li>
            Temple of Sethraliss
            <ul>
                <li>
                    Moved a pack of Agitated Nimbus and Imbued Stormcallers a bit further north before the third boss.
                </li>
            </ul>
        </li>
        <li>
            Waycrest Manor
            <ul>
                <li>
                    Added Dreadwing Ravens to the Upstairs floor.
                </li>
                <li>
                    Updated some patrols on Upstairs floor, added dungeon floor switches.
                </li>
            </ul>
        </li>
    </ul>
    </p>

    <h4>
        v1.3 (2018/10/28)
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
        v1.2 (2018/10/27)
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
        v1.1.1 (2018/10/18)
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
        v1.1 (2018/10/16)
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
        v1.0.5.1 (2018/10/13)
    </h4>

    <p>
        Map changes:
    <ul>
        <li>
            Corrected Teeming enemy forces needed for The Underrot, King's Rest.
        </li>
    </ul>
    </p>

    <h4>
        v1.0.5 (2018/10/12)
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
        v1.0.4 (2018/10/11)
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
        v1.0.3 (2018/10/11)
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
        </li>
        <li>
            The MOTHERLODE!!
            <ul>
                <li>
                    Stonefuries before Taskmaster Askari are now linked.
                </li>
                <li>
                    Numerous corrections to The D.M.Z. before the last boss (missing bombs, slightly off locations).
                </li>
            </ul>
        </li>
        <li>
            The Underrot
            <ul>
                <li>
                    Added a missing pack of 2 patrolling Diseased Lashes between the first and second boss.
                </li>
            </ul>
        </li>
        <li>
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
        v1.0.2 (2018/10/09)
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
        v1.0.1 (2018/10/08)
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
        v1.0 (2018/10/07)
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