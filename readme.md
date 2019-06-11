# About Keystone.guru
Keystone.guru is a website where users can build and find their favorite routes for completing in Mythic Plus in World of Warcraft®: Battle for Azeroth™.
This repository contains the source code of (most parts of) the website. It is built with [Laravel](https://github.com/laravel/laravel) using [jQuery](https://github.com/jquery/jquery), [PHP](http://www.php.net/) and [MySql](https://www.mysql.com/).
It uses a ton of other libraries such as Leaflet for map displaying, Puppeteer for thumbnail generation, REDIS for cache, Laravel Echo Server for real-time communication and much, much more.

Currently the website has the following features:

* Full interactive Leaflet map of all Battle for Azeroth™ dungeons
* All known enemies and patrols inside dungeons are mapped and displayed on the map (updated to 8.1.5)
* Enemy forces of all different NPCs in both normal and Teeming weeks are catalogued
* Route planning functions such as
  * Plotting a route through the dungeon, allowing a route to split up should the need arise
  * Free-drawing of lines
  * Selecting which enemies to kill and where
  * Free-form comments to indicate difficult enemies, strategies to use or abilities to use
  * Publish your planned route whenever you're ready to share it
  * Attach attributes to your route to give an indication of difficulty (death/invisibility skips or classes required)
* User voting; increase visibility of your favorite routes
* Favorite routes you liked best for easy reference later on
* Search function for looking up routes others have made
  * Search by dungeon, affixes, attributes and/or favorite state
  * Full sorting by dungeon, affixes, author, views and ratings
  * Multiple route listing display modes to suit your needs
* Option for creating private routes (Patreonage required)
* When not registered, you are free to view any routes found in the search function, or when directly linked by others
  * Tryout mode available in which you can sandbox your route/the website with no strings attached
  * NEW: Edit your routes in real-time with your friends, Google Docs-style!
  
# Not included in this repository
* Map tiles of all dungeons
* Software used for creating said map tiles (self-made)

# Contributing
Contribution can be done in a lot of ways in this project! If you got programming or artist skills and wish to contribute, I could use help! Please raise an issue here or send me a dm on Discord (Wotuu#1937) so I can help you get started on something cool!

# Security Vulnerabilities
Any security vulnerabilities should be reported directly to myself on Discord (Wotuu#1937) or an e-mail to security@keystone.guru. It is _greatly_ appreciated if you do this prior to mentioning the vulnerability in public.

If you found a security vulnerability, do not abuse the vulnerability for more than is reasonably necessary to confirm the issue exists.

# License
Currently this project has [no license](https://choosealicense.com/no-permission/) attached to it while I explore the options of licensing. 
If you have any suggestions for a fitting license please let me know!

# Contact
Found an issue? Want to leave some feedback? Can't figure something out? Please drop by on [Discord](https://discord.gg/2KtWrqw), open an issue on GitHub
or send an e-mail to support@keystone.guru.

# Disclaimer
World of Warcraft, Warcraft and Blizzard Entertainment are trademarks or registered trademarks of Blizzard Entertainment, Inc. in the U.S. and/or other countries. This repository/website is not affiliated with Blizzard Entertainment.