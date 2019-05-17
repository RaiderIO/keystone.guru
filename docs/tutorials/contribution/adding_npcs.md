## Adding new NPCs
If you're not sure of what an NPC is vrs an enemy, view the `structure_overview.md` file.
This is important.

<aside class="notice">
You need to be an admin in order to access these pages. Contact me in order to be made an admin.
</aside>

Start by accessing the NPC overview as follows:

![View NPCs](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_npcs_1.png "NPCs")

You will see the following screen:

![NPC overview](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_npcs_2.png "NPCs overview")

With more entries than that, but you get the point. You can use the filter to check if the NPC
you wish to add already exists. If it does not, go ahead and press the `Create NPC` button.

![NPC creation](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_npcs_3.png "NPCs creation")

Let's say we want to add a Valarjar Champion. We start by looking up the NPC on https://wowhead.com/

![Wowhead](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_npcs_4.png "Wowhead")

Which do you pick? Usually you want the top entry, as we do in this case. It has the location we're looking for (`Halls of Valor`)
, and `React` is set to a red A and H. This means the NPC is hostile to both Alliance and Horde. The
other entries are probably used for cut-scenes, special scenarios etc.

Now back to our NPC creation form.

### Game ID
This is the ID the game has assigned for this NPC. You can find this in the URL of wowhead:

![Wowhead](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_npcs_5.png "Wowhead")

### Name
The name of the NPC, goes without saying.

### Dungeon
Select the dungeon that this NPC is residing on. In this case `Halls of Valor`.

### Classification
* Normal: just a normal mob (usually these are very weak monsters in dungeons)
* Elite: just about every single mob in a dungeon (golden dragon around its portrait)
* Boss: special marker to indicate a boss

### Aggressiveness
* Aggressive: will attack you when you get close (red name)
* Unfriendly: orange name, will usually turn aggressive soon
* Neutral: will only attack when provoked (yellow name)

### Base health
The health that the NPC has on Mythic 0! Not on normal, not on Heroic, on Mythic 0 ONLY! Enter 1 
if you do not know.