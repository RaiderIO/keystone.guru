## Structure
The website is built with a specific structure in mind. 
Without boring people with a large ERD, I will write what you need to know.

### Expansion
An expansion is a definition of a name, an icon, and a color. 
You could see this as the root of most things, 
though it really is only referenced by one thing. Which is:

### Dungeon
A dungeon belongs to an expansion. A Dungeon is one of the most connected elements
of the website, it just about contains everything, though a lot of things are directly
coupled to a specific floor (a dungeon contains one or more floors).

### Floor
A floor is where most things are added to. NPCs (non-player characters) are _not_ 
added to floors. Enemies (which is an NPC at a location), enemy packs (groups of enemies), 
patrols and more are part of a Floor.

### NPC
An NPC is a plain definition of a character you see in-game. You could see an NPC
as a brand+type of car. You can define a Ford Focus in here, along with some basic 
settings (it has 4 wheels, front lights, brake lights), but not for example what color
the car has. This definition thus contains all things that all cars have in common.
Whatever options are selected for the car is specific to your car. I have a silver car,
but your neighbour can have a black car. They're still the same brand+type, but some things
are different.

Back to WoW, an NPC is all fields that enemies have in common. For example, you can
have the definition of a [Valarjar Champion](https://www.wowhead.com/npc=97087/valarjar-champion)
here. The NPC definition would contain the name, its max hitpoints, its abilities etc.
An enemy is a 'spawned instance' of this NPC. You can have multiple enemies of the same
NPC type. Packs usually contain more of the same NPC. If you add an NPC once to the website,
you can then use that definition to create lots of enemies of the same type.

### Enemy
As stated above, not very much needs to be said here. An enemy is coupled to an NPC and
is placed on the map. It may be part of an Enemy Pack if the pack is created over the enemy,
or if the enemy is placed in the pack.

### Enemy pack
An enemy pack is a group of enemies. If you pull one enemy from the pack, you pull the rest.
If enemies can be pulled from a physical cluster of enemies WITHOUT aggroing the entire group,
that group is NOT a pack. Sometimes you can pull things that look like a pack, but only pull 
one of them. Think of the bears on Teeming Darkheart Thicket before the first boss. You can
pull them one at a time, I believe. Thus, those two are no pack but rather individually placed
enemies.

### Patrol
A patrol is an enemy that walks a certain route across the dungeon.

### Floor switch marker
This is a special marker which indicate stairs or passageways from one floor to another.
This makes it easier to navigate from one floor to another.

### Dungeon start marker
Indicates the start of a dungeon which is where players spawn.