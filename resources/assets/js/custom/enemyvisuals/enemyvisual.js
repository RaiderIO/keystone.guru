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

            let width = size.iconSize[0];
            let height = size.iconSize[1];

            let margin = c.map.enemy.calculateMargin(width);

            data.id = this.enemy.id;
            // Compensate for a 2px border on the inner, 2x border on the outer
            data.inner_width = 'calc(100% - ' + (margin * 2) + 'px)';
            data.inner_height = 'calc(100% - ' + (margin * 2) + 'px)';

            data.outer_width = (width + (margin * 2)) + 'px';
            data.outer_height = (height + (margin * 2)) + 'px';

            data.margin = margin;

            data.modifier_0_left = (width / 2) + margin - 26;
            data.modifier_1_left = (width / 2) + margin - 8;
            data.modifier_2_left = (width / 2) + margin + 10;

            // Create a new div icon (the entire structure)
            this.divIcon = new L.divIcon({
                html: template(data),
                iconSize: [width + (margin * 2), height + (margin * 2)],
                tooltipAnchor: [0, ((height * -.5) - margin)],
                popupAnchor: [0, ((height * -.5) - margin)]
            });

            // Set the structure as HTML for the layer
            this.layer.setIcon(this.divIcon);
            this.signal('enemyvisual:builtvisual', {});
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
        // let isBoss = this.enemy.npc !== null && this.enemy.npc.classification_id === 3;
        // if (isBoss) {
        //     name = 'npc_class';
        // }

        switch (name) {
            case 'npc_class':
                this.mainVisual = new EnemyVisualMainEnemyClass(this);

                this.modifiers = [
                    new EnemyVisualModifier(this, 0),
                    new EnemyVisualModifierRaidMarker(this, 1),
                    new EnemyVisualModifier(this, 2),
                ];
                break;
            case 'npc_type':
                this.mainVisual = new EnemyVisualMainNpcType(this);

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
}