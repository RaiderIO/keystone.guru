class EnemyVisual extends Signalable {
    constructor(map, enemy, layer) {
        super();
        console.assert(map instanceof DungeonMap, map, 'map was not a DungeonMap');
        console.assert(enemy instanceof Enemy, enemy, 'enemy was not an Enemy');

        this.map = map;
        this.enemy = enemy;
        this.layer = layer;
        this.divIcon = null;

        this.mainVisual = null;

        this.modifiers = [];

        // Default visual (after modifiers!)
        this.setVisualType(getState().getEnemyDisplayType());

        let self = this;
        // Build and/or destroy the visual based on visibility
        this.enemy.register(['shown', 'hidden'], this, function (event) {
            if (event.data.visible) {
                self._buildVisual();
            } else {
                // When an object is hidden, its layer is removed from the parent, effectively rendering its display nil.
                // We don't need to do anything since if the visual is added again, we're going to re-create it anyways
            }
        });
    }

    /**
     * Constructs the structure for the visuals and re-fetches the main visual's and modifier's data to re-apply to
     * the interface.
     * @private
     */
    _buildVisual() {
        console.assert(this instanceof EnemyVisual, this, 'this is not an EnemyVisual');

        // If the object is invisible, don't build the visual
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        if (enemyMapObjectGroup.isMapObjectVisible(this.enemy)) {
            let template = Handlebars.templates['map_enemy_visual_template'];

            let data = {};

            if (this.enemy.isSelectable()) {
                data = {
                    selection_classes_base: 'leaflet-edit-marker-selected selected_enemy_icon'
                };
            }

            data = $.extend(data, this.mainVisual._getTemplateData());
            for (let i = 0; i < this.modifiers.length; i++) {
                data = $.extend(data, this.modifiers[i]._getTemplateData());
            }

            let size = this.mainVisual.getSize();
            data.id = this.enemy.id;
            data.width = size.iconSize[0];
            data.height = size.iconSize[1];

            // console.log('VISIBLE', this.enemy.id, this.enemy.npc.name, data.width);

            data.modifier_0_left = (data.width / 2) - 26;
            data.modifier_1_left = (data.width / 2) - 8;
            data.modifier_2_left = (data.width / 2) + 10;

            // Create a new div icon (the entire structure)
            this.divIcon = new L.divIcon($.extend({html: template(data)}, size));

            // Set the structure as HTML for the layer
            this.layer.setIcon(this.divIcon);
            this.signal('enemyvisual:builtvisual', {});
        } else {
            // console.log('INVISIBLE', this.enemy.id, this.enemy.npc.name);
        }
    }

    // @TODO Listen to killzone selectable changed event
    refresh() {
        console.assert(this instanceof EnemyVisual, this, 'this is not an EnemyVisual');

        // Refresh the visual completely
        this.setVisualType(getState().getEnemyDisplayType());
    }

    /**
     * Sets the visual type for this enemy.
     * @param name
     */
    setVisualType(name) {
        // Let them clean up their mess
        if (this.mainVisual !== null) {
            this.mainVisual.cleanup();
        }
        for (let i = 0; i < this.modifiers.length; i++) {
            this.modifiers[i].cleanup();
        }

        // @TODO Create boss visual
        // Create a new visual based on the requested visual. With one exception, bosses are always shown with the
        // Aggressiveness filter. Otherwise they show up as things we don't want them to
        // @TODO Hard coded 3 = boss
        let isBoss = this.enemy.npc !== null && this.enemy.npc.classification_id === 3;
        if (isBoss) {
            name = 'aggressiveness';
        }

        switch (name) {
            case 'npc_type':
                this.mainVisual = new EnemyVisualMainNpcType(this);

                this.modifiers = [
                    new EnemyVisualModifier(this, 0),
                    new EnemyVisualModifierRaidMarker(this, 1),
                    new EnemyVisualModifier(this, 2),
                ];
                break;
            case 'aggressiveness':
                this.mainVisual = new EnemyVisualMainAggressiveness(this);

                this.modifiers = [
                    new EnemyVisualModifier(this, 0),
                    new EnemyVisualModifierRaidMarker(this, 1),
                    new EnemyVisualModifier(this, 2),
                ];
                break;
            case 'enemy_forces':
                this.mainVisual = new EnemyVisualMainEnemyForces(this);

                this.modifiers = [
                    new EnemyVisualModifier(this, 0),
                    new EnemyVisualModifierRaidMarker(this, 1),
                    new EnemyVisualModifier(this, 2),
                ];
                break;
        }

        this._buildVisual();
    }

    // /**
    //  *
    //  * @param name
    //  */
    // setMainIcon(name) {
    //     console.assert(this instanceof EnemyVisual, this, 'this is not an EnemyVisual');
    //
    //     this.mainVisual.setIcon(name);
    //     this._buildVisual();
    // }
    //
    // /**
    //  * Set a modifier's icon name by index. Pass null or an empty string to the name to unset.
    //  * @param index
    //  * @param name
    //  */
    // setModifierIcon(index, name) {
    //     console.assert(index >= 0 && index <= 2, this, 'Index is out of bounds!');
    //     console.log(">> setModifierIcon", index, name);
    //
    //     // Find the modifier of the index
    //     let modifier = this.modifiers[index];
    //     // Let it figure out its own icon by setting the name
    //     modifier.setIcon(name);
    //     this._buildVisual();
    //     console.log("OK setModifierIcon", index, name);
    // }
}