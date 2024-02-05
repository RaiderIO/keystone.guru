![alt text](https://keystone.guru/images/external/github/readme_logo.png "Keystone.guru logo")

# About Keystone.guru
Keystone.guru is a website where users can build and find their favorite routes for completing in Mythic Plus in World of Warcraft®: Shadowlands™.
This repository contains the source code of (most parts of) the website. It is built with [Laravel 8.0](https://github.com/laravel/laravel) using [jQuery](https://github.com/jquery/jquery), [PHP](http://www.php.net/) and [MySQL](https://www.mysql.com/).
It uses a ton of other libraries such as Leaflet for map displaying, Puppeteer for thumbnail generation, REDIS for cache, Laravel Echo Server for real-time communication and much, much more.

The website is free to use, now and forever. You may support the development of this website by becoming a Patron on [Patreon](https://www.patreon.com/keystoneguru).

# Features
* Full interactive Leaflet map of all Classic™, Wrath of the Lich King Classic™, Battle for Azeroth™, Shadowlands™ and Dragonflight™ dungeons
* Enhanced dungeon map images to allow for 5 zoom levels
  * Dungeon mapping shared with Mythic Dungeon Tools for a seamless integration (thanks to Nnoggie for collaborating)
* Full import/export support for Mythic Dungeon Tools strings
* Discover page where you can easily find popular routes created by other users
* Search function for looking up routes others have made
  * Search by dungeon, affixes, target key level, ratings, author or whether enemy forces have been reached or not
* An up-to-date overview of upcoming affixes and their time based on your region
* Generate a SimulationCraft string to simulate your route and optimize your pulls
  * Takes into account travel distance between pulls, enemy health, party-wide buffs etc.
* View dungeon mapping without creating a route through the Explore section
* Temporary routes available in which you can try out a quick route or the website at large with no strings attached
* Mobile friendly!
* In collaboration with Raider.io, the Auto Route Creator keeps track of your routes as you do them in-game. For more info see the [Raider.io article](https://raider.io/news/538-introducing-the-auto-route-creator) on the Auto Route Creator

# Registered user features
* Register through Google, Discord or Battle.net if you don't want to create a Keystone.guru account
* Route planning functions such as
    * Plotting a path through the dungeon, allowing a route to split up should the need arise
    * Free-drawing of lines
    * Easy creation of pulls of which enemies to kill and optionally where
        * Assign abilities that your party should utilize directly to a pull
        * Add a description to a pull
        * Manage your pull colors by applying a custom gradient
    * Various icons with optional comments to indicate difficult enemies, strategies to use or abilities to use
    * Various publishing options for your route - keep it private, share it with your team or with everyone
    * Attach attributes to your route to give an indication of difficulty (death/invisibility skips or classes required)
* Collaboration with your group members through Teams
    * Attach routes to a Team for easy sharing with your group members
    * View/edit routes in real-time Google Docs-style, synchronizing your changes to all other viewers or editors
    * Permission management for who can view or edit routes attached to your team
* Favorite routes you liked best for easy reference later on
* Tag routes for managing your stockpile of routes

# Patreon features
* No ads
* Create animated lines on the map
* Create unlisted private routes that can be shared with others by link

# Revered Patreon features
* Enhanced SimulationCraft support such as mount support
* Grant an ad-free experience to up to 5 of your team members

# Developer docs
The Swagger documentation that describes the API can be found at https://keystone.guru/api/documentation. Need a specific endpoint for your tool? Let me know, and I'll see what I can do for you.

# Not included in this repository
* Map tiles of all dungeons
* Software used for creating said map tiles (self-made)

# Contributing
Contribution can be done in a lot of ways in this project! If you got programming or artist skills and wish to contribute, I could use help! Please raise an issue here or send me a dm on Discord (Wotuu#1937) so I can help you get started on something cool!

# Security Vulnerabilities
Any security vulnerabilities should be reported directly to myself on Discord (Wotuu#1937) or an e-mail to security@keystone.guru. It is _greatly_ appreciated if you do this prior to mentioning the vulnerability in public.

If you found a security vulnerability, do not abuse the vulnerability for more than is reasonably necessary to confirm the issue exists.

# License
At this time this project has [no license](https://choosealicense.com/no-permission/) attached to it while I explore the options of licensing.
If you have any suggestions for a fitting license don't hesitate to raise an issue.

# Contact
Found an issue? Want to leave some feedback? Can't figure something out? Please drop by on [Discord](https://discord.gg/2KtWrqw), open an issue on GitHub
or send an e-mail to support@keystone.guru.

# Disclaimer
World of Warcraft, Warcraft and Blizzard Entertainment are trademarks or registered trademarks of Blizzard Entertainment, Inc. in the U.S. and/or other countries. This repository/website is not affiliated with Blizzard Entertainment.
