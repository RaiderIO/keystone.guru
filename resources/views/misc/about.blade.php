@extends('layouts.sitepage', ['showLegalModal' => false, 'title' => __('views/misc.about.title')])

@section('header-title', __('views/misc.about.header'))

@section('content')
    <h5>
        Hello!
    </h5>
    <p>
        My name is Wouter from the Netherlands, author of Keystone.guru. I'm a software developer with some 10 years
        experience
        working in various parts of the field. World of Warcraft player for longer. I'm pleased to see you're visiting
        my website
        and hopefully contributing something nice, or use it to help coordinate your Mythic Plus keystones between your
        friends or PUGs. I truly hope it improves the quality of your playtime, I have had a lot of fun and learnt a LOT
        making this website, and having a finished product that I can expand upon is great. I play on Lightbringer-EU -
        Horde
        as a Resto Druid in a fabulous casual guild called <a href="http://darkwolves.eu/">Dark Wolves</a> with my
        girlfriend and a
        bunch of (RL) friends. The guild is always looking for new members, so feel free to check out the website.
        <a href="https://worldofwarcraft.com/en-gb/character/lightbringer/laronia">Check me out</a> as well!
    </p>
    <h5>
        Some history
    </h5>
    <p>
        At the start of Legion the Mythic Plus system was introduced and as a fan of a challenge, I was hooked quickly.
        Together with guildies and friends, we poured ourselves on the dungeons and started whacking away. We weren't
        very
        good, nor will I claim we are to this day, but we're trying! One thing that always bothered us was a lack of
        proper
        communication. We never quite had the same group of people online to do the dungeons we wanted to, so sometimes
        we had unsuccessful runs because of miscommunication, differences in strategies (oh we always go left here!),
        people completely new to the dungeons/fights etc. Never mind joining a pug, it was - as I'm sure you are aware
        of -
        hit and miss.
    </p>
    <p>
        Something had to be done about this.
    </p>
    <p>
        Enter the idea for this website. I thought of making such a website and figured I really wanted to build it, so
        at the end of 2017 I started working on getting the dungeon maps into a Google Maps map. Not such an easy task,
        but with some Java and image magic I doubled the resolution of the existing maps (which works, for now) and made
        a tool to split the images up and feed it into Google Maps. It worked and I could browse through all dungeons,
        levels and zoom in/out.
    </p>
    <p>
        After that, I kind of lost steam and the project stalled for a month or two. I had ideas, but I never made a
        real,
        functioning website before, not to mention getting a proper layout going and making it look better than digital
        programmer vomit. I'm a software developer, not an artist! At that time, there was some reorganization going on
        at my place of employment which led me getting 10% of my work-time to work on projects that use technologies
        relevant
        for the company. The idea for the website was re-born.
    </p>
    <p>
        From that point, I spent my '10% time' at work on the project, and a LOT of evenings at home to wrap it all up.
        After all, I had a deadline of releasing before the first Mythic Plus season in Battle for Azeroth. It included
        a lot of learning and integrating existing projects and libraries. At this point in time it's a week before BfA
        hits and I'd really like to do the pre-quests, but I gotta finish the website first. Doh. There's still a lot
        to be done, so I hope I wrap it all up in-time for a proper release. And that my poor web server handles the
        traffic.
    </p>
    <h5>
        Now
    </h5>
    <p>
        When the website is finished, I hope it fills a gap that I currently think exists in the (for now, casual)
        Mythic Plus scene: being able to share pre-made routes between your friends and random people so that everyone
        has a common understanding of what you're about to do. It can also help new players read up about what
        strategies
        people are currently using in dungeons (by means of popularity) so they can prepare themselves better. For now
        the website is fairly bare. You can select your dungeon, composition, and draw a path through the dungeon. I
        will
        expand on this in the near future. There were time constraints to consider and I can always expand on current
        functionality.
        If you don't think the website has enough features as of yet, please stay tuned! I'm dedicated to turning this
        website
        into a hub for sharing information and catering to increasingly hardcore players of the scene. For now it will
        fit
        casual people best, but I'm looking to include features down the line which more hardcore players find useful.
    </p>

    <h5>
        Can I help?
    </h5>
    <p>
        Your feedback is important to me!
        No really, it is. If you got any questions or suggestions, please pop in our discord, or make a ticket on
        <i class="fab fa-github"></i> Github! There's a lot of ideas in my head to make this website better but I feel
        it's best to crowd-source ideas so we all get the best operating website possible.
    </p>
@endsection
