@extends('layouts.app', ['showLegalModal' => false, 'title' => __('Changelog')])

@section('header-title', __('Changelog'))

@section('content')
    <h4>
        v2.5.3 (2019/06/18)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/185">#185</a>
            Interally reworked the way affixes and affix groups are handled (a group is pretty much an affix set that
            you encounter every week). This now works with M+ Seasons, allowing for cleaner code and making it very
            easy to add new affixes/seasons. The only change for you is that the /affixes page will be more accurate
            going into the future, around the weeks where a new season is announced.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/139">#139</a>
            Patrols and enemy packs can now be marked as 'teeming' and belonging to a specific faction (think Siege of
            Boralus).
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/169">#169</a>
            Added simple custom error pages. I'm looking to refine these further, but they're in Keystone.guru style
            now.
        </li>
        <li>
            As part of the above tickets, I've also fixed an issue that was introduced with the introduction of Teams
            where filtering Routes on the /routes page or in your profile, teams pages would no longer work. This has
            been corrected.
        </li>
    </ul>

    <h4>
        v2.5.2 (2019/06/13)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/179">#179</a>
            Added an admin dashboard.
        </li>
    </ul>

    <h4>
        v2.5.1 (2019/06/12)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/37">#37</a>
            Fixed multiple issues with the new co-operative route editing feature.
        </li>
    </ul>

    <h4>
        v2.5 (2019/06/12)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/37">#37</a>
            You can now co-operatively edit your routes with your Team members! To get started, create a group, invite
            your party and finally set them to be collaborators in the "members" section. Add some routes to the team,
            then start editing them with your team members. All changes you and your team members perform will be
            synchronized to each other, Google-docs style!
        </li>
    </ul>

    <h4>
        v2.4 (2019/05/16)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/167">#167</a>
            Group selection when creating/editing routes has been fixed.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/166">#166</a>
            Added support for Kul Tiran and Zandalari Trolls.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/77">#77</a>
            Added a new feature: Teams! You can now create a group in which you can group up routes, this allows for a
            much easier way to share specific routes with the people you usually play with.
        </li>
        <li>
            Reworked the Profile view (again!) to match the new Teams feature.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/131">#131</a>
            Implemented OAuth integration with Battle.net, Discord and Google. You can now login/register using those
            providers.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/162">#162</a>
            Redid the backend on the way Routes are displayed in the various tables found on the site.
        </li>
    </ul>
    <p>
        Map changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/135">#135</a>
            Map tiles have been reworked and re-upscaled. They look a lot better now; this also fixed the issue where
            zooming in the map would expose some misalignment in the tiles.
        </li>
    </ul>

    <h4>
        v2.3 (2019/04/10)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/154">#154</a>
            Reduced loading times of various pages and increased performance.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/150">#150</a>
            Switching floors in 'Try' mode no longer clears all mapping progress. As a result of changes for this, I've
            added functionality to save the current mapping as a new Route. If you're not logged in, there's an option
            to log in and continue mapping, if you do not have an account you can now also register from the same page
            and continue mapping. Furthermore, changes for this ticket will allow me to more easily allow people to
            create anonymous Routes.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/153">#153</a>
            Cloning a route now properly clones free drawn shapes.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/160">#160</a>
            Updated notifications so they no longer show in a bar at the bottom, but neatly in the top right corner
            instead.
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            I've done some fine-tuning and bug fixing for the general layout of the map, in both desktop and mobile
            versions.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/156">#156</a>
            Worked around an issue where sometimes a Route would cause freezing of the entire browser tab. I cannot
            directly fix this issue as I believe it lays in another library I use, but I've contacted the author of said
            library and hopefully I'll have a proper fix soon. For now it seems fixed and is very rare regardless, so
            hopefully it stays gone til the fix is there.
        </li>
    </ul>
    </p>

    <p>
        MDT importer changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/157">#157</a>
            Updated to MDT version 2.5.6, all changes since 2.3.2 will now properly import, such as pull colors.
        </li>
    </ul>
    </p>

    <p>
        Mapping changes (for patch 8.1.5)
        <a href="https://github.com/Wotuu/keystone.guru/issues/158">#158</a>:
    <ul>
        <li>
            Atal'Dazar
            <ul>
                <li>
                    Added a Teeming Dazar'ai Confessor towards the south side of the dungeon, after the first two packs
                    in a pack with two Dazar'ai Colossi.
                </li>
                <li>
                    Removed two Teeming Shieldbearer of Zul in the pack before Vol'kaal.
                </li>
            </ul>
            Freehold
            <ul>
                <li>
                    The start of the dungeon is now correctly marked on the map.
                </li>
                <li>
                    Removed a Teeming Irontide Bonesaw, a non-Teeming Irontide Bonesaw and a Teeming Irontide Enforcer
                    from the large patrolling pack right before Skycap'n Kragg.
                </li>
                <li>
                    Removed a Teeming Irontide Officer in the large pack before Harlan Sweete.
                </li>
            </ul>
            King's Rest
            <ul>
                <li>
                    Removed a Teeming Spectral Brute before the second to last boss.
                </li>
            </ul>
            Siege of Boralus (Alliance)
            <ul>
                <li>
                    Added a Dockhound Packmaster and Snarling Dockhound in a pack after passing through the first gate,
                    after the market section, before the first boss.
                </li>
                <li>
                    In that same pack, added a Teeming Scrimshaw Enforcer.
                </li>
            </ul>
            Shrine of the Storm
            <ul>
                <li>
                    Removed multiple Abyss Dweller in the room before the bridge to the last section.
                </li>
                <li>
                    Removed multiple duplicate Abyssal Eel in the last room.
                </li>
            </ul>
            Temple of Sethraliss
            <ul>
                <li>
                    Removed a duplicate Sandswept Marksman in the pack entering Aspix' and Adderis' room, when taking
                    a left initially.
                </li>
                <li>
                    Aspix is no longer incorrectly marked as an Eye of Sethraliss.
                </li>
            </ul>
            The MOTHERLODE!!
            <ul>
                <li>
                    Added Mech Jockeys in the first section next to their respective Mechanized Peacekeepers.
                </li>
                <li>
                    Removed 3 Teeming Wanton Sappers in the big pack after the first boss.
                </li>
                <li>
                    Removed a lot of the Teeming Crawler Mines in the last section.
                </li>
            </ul>
            The Underrot
            <ul>
                <li>
                    Removed a Underrot Tick in the second pack from the start before the first boss.
                </li>
                <li>
                    Removed a Teeming Living Rot in a pack right after the first boss.
                </li>
            </ul>
            Tol Dagor
            <ul>
                <li>
                    Removed a Teeming Sewer Vicejaw in the Sodden Depths.
                </li>
                <li>
                    Removed a duplicate Ashvane Marine in the pack right before Knight Captain Valyri.
                </li>
                <li>
                    Removed a duplicate Ashvane Warden right after Knight Captain Valyri.
                </li>
            </ul>
            Waycrest Manor
            <ul>
                <li>
                    Removed a pack of Devouring Maggots and Infested Peasants when coming from the south towards Raal
                    the Gluttonous' room.
                </li>
                <li>
                    Removed two Teeming Heartsbane Soulcharmers in Lady Waycrest's room.
                </li>
            </ul>
        </li>
    </ul>
    </p>

    <h4>
        v2.2.2 (2019/03/07)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/149">#149</a>
            The MDT importer will now show warnings when something isn't right, rather than outright failing. Also
            updated the UI to be more user friendly.
        </li>
    </ul>
    </p>

    <h4>
        v2.2.1 (2019/03/04)
    </h4>
    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/146">#146</a>
            Fixed an error that occurred MDT Importer when your imported routes had any drawn lines.
        </li>
    </ul>
    </p>

    <h4>
        v2.2 (2019/03/04)
    </h4>
    <p>
        Description: <br>
        This release focuses on improving some user interface elements. I'm not done yet with the changes, but this
        should be a good improvement already regardless.
    </p>

    <p>
        General changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/137">#137</a>
            Assets are now versioned; this means your cached version of Keystone.guru will be invalidated whenever I
            push an update. This should prevent errors from occurring when taking an old website (your cached version)
            and having it talk to the new back-end (should there be such breaking changes).
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/118">#118</a>
            The (buggy) free drawing tool has been replaced by a new tool. This one should be bug-free and offer a much
            better user experience. Draw away!
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/140">#140</a>
            The drawing tools are no longer located in the sidebar. Instead, they are now found in a new floating bar
            that lives on the lower side of your screen. This should reduce the amount of travel your mouse has to do,
            and puts all items in a more convenient place. This also aids mobile users in creating their routes. In the
            future I will be looking at adding more elements to this bar so that the sidebar should no longer be needed
            for general usage, or can be removed entirely.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/141">#141</a>
            Removed the popup to select a raid marker when clicking on an enemy. Instead, a circle menu pops out and
            allows you to select the raid marker that way. This should make the process a tad quicker and more
            aesthetically pleasing.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/142">#142</a>
            Enemy forces are now displayed in the above mentioned floating bar.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/143">#143</a>
            Updating your enemy forces (through adding enemies to a Killzone) will now briefly flash the enemy forces
            display with a green color, rather than showing a small popup.
        </li>
    </ul>
    </p>

    <h4>
        v2.1 (2019/02/20)
    </h4>
    <p>
        Description: <br>
        This release focuses on improving the codebase, increasing performance and tying up some loose ends in the code.
        There will be some improvements and bug fixes but no major new features. I will be focussing on that in next
        releases now that the codebase is much stronger than before.
    </p>
    <p>
        General changes:
    <ul>
        <li>
            Fixed Virtual Tour not working for dungeons with just one floor.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/115">#115</a>
            Changed the internal structure of all line-based objects. This results in a good performance increase now
            and especially going into the future as the amount user-generated Routes keep growing.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/85">#85</a>
            Reduced the amount of requests upon startup, increasing the speed at which the page loads, especially for
            those not situated in Europe.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/110">#110</a>
            Pre-compiled most Handlebars templates. Increases performance and reduces load on the local machine.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/132">#132</a>
            Any new Route that you create would not save its affixes; this has been resolved (some time ago already,
            but it's in this release now).
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/124">#124</a>
            Revamped the menu while on mobile. It looks a lot better now.
        </li>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/133">#133</a>
            When you delete a Route it's now cleaned up more thoroughly.
        </li>
    </ul>
    </p>

    <p>
        Map changes:
    <ul>
        <li>
            <a href="https://github.com/Wotuu/keystone.guru/issues/88">#88</a>
            Routes are now called Paths. The term 'Route' was ambiguous with your entire run, which is also called a
            Route. Now the entire plannable dungeon is called a Route, the path which you take is comprised of Paths.
        </li>
    </ul>
    </p>

    <p>
        Mapping changes:
    <ul>
        <li>
            Various packs that enemies can be part of have been corrected. Some enemies were not part of a pack when
            they should've been, or were part of the wrong pack.
        </li>
        <li>
            Fixed an issue in Waycrest Manor in The Catacombs that caused Javascript errors, leading to potentially
            unresponsive pages.
        </li>
    </ul>
    </p>

    <h4>
        v2.0.2 (2019/02/05)
    </h4>
    <p>
        General changes:

    <ul>
        <li>
            Fixed Virtual Tour not working properly.
        </li>
        <li>
            Assigned raid markers are now displayed properly again.
        </li>
    </ul>
    </p>

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
            <a href="https://github.com/Wotuu/keystone.guru/issues/95">#95</a>
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
    </p>

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
            {{ config('keystoneguru.infested_user_vote_threshold', 3) }} more yes votes than no votes to be marked
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