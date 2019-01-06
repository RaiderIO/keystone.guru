## Adding new enemies
If you're not sure of what an NPC is vrs an enemy, view the `structure_overview.md` file.
This is important.

<aside class="notice">
You need to be an admin in order to access these pages. Contact me in order to be made an admin.
</aside>

An enemy is a dot on the map on the website. These are the actual enemies you encounter in the dungeon.
This is an integral part of the website, for without it the website is useless. Let's get going.

### Editing floors
An enemy is always placed on a single Floor. Remember, a Dungeon can have one or many Floors. Each floor
corresponds to a map in the dungeon (like, the in-game map). If the in-game map has multiple levels,
there's an equal amount of floors. 

To edit floors:

![Edit dungeon](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_enemies_1.png "Add enemies")

Find the dungeon you're looking for in the list, and click Edit

![Edit dungeon](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_enemies_2.png "Add enemies")

From the next screen, select the floor for which you will be adding enemies. You will not need to edit any information here. 

![Edit floor](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_enemies_3.png "Edit floor")

You will not need to edit any information here. Scroll a bit to the bottom and you'll see an overview of the
selected floor titled `Enemy placement`.

To the left you can see a control panel (on the map) which allows you to interact with the map and place enemies.
t
![Control panel](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_enemies_4.png "Control panel")

1. Zooms in the map (or use ctrl + mouse wheel)
2. Zooms out the map (or use ctrl + mouse wheel)
3. Draws an enemy pack (hotkey: a)
4. Draws an enemy (hotkey: e)
5. Draws an enemy patrol (hotkey: p)
6. Draws a 'dungeon start marker'
7. Draws a 'floor switch marker'
8. Edits all current elements so you can move them around (hotkey: c)
9. Allows you to select an element for deletion (hotkey: d)
10. Toggles display of enemies on/off
11. Toggles display of patrols on/off
12. Toggles display of enemy packs on/off
13. Toggles display of dungeon start markers on/off
14. Toggles display of floor switch markers on/off

### Actually adding enemies to the map

Hit one of the control buttons on the left (labelled 3 through 7) and move your cursor over the map. In the case of an enemy
you will have a yellow dot underneath your mouse. Place it where you think the enemy is on the map. Next, LEFT click on the
newly placed enemy. A popup will show up.

![Popup](https://raw.githubusercontent.com/Wotuu/keystone.guru/development/resources/assets/images/tutorials/contribution/add_enemies_5.png "Popup")

Teeming settings can be ignored for now, it allows to hide/show enemies when the Teeming affix is selected. As for the NPC,
this is where you select the NPC that was created in the previous tutorial. Once you selected your NPC, click Submit and your enemy is saved.

Repeat this process for all enemies you wish to add, and you're done!

### Enemy packs

An enemy pack is the definition of a group of enemies that all come at you the moment you pull one of them. To draw an enemy
pack, use tool 3. Encircle the already placed enemies with a fair margin so that the enemies fall in the pack with a decent amount
of space. When zoomed out, everything becomes smaller, so it must still be visible to see it's a pack when the user is fully zoomed out.