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

        // If it changed, refresh the entire visual
        this.enemy.register('enemy:set_raid_marker', this, this._buildVisual.bind(this));
    }

    /**
     * Creates modifiers that alter the display of the visual
     * @returns {Array}
     * @private
     */
    _createModifiers() {
        let modifiers = [];
        // Only add the modifiers if they're necessary; otherwise don't waste resources on adding hidden items
        if (typeof this.enemy.raid_marker_name === 'string' && this.enemy.raid_marker_name !== '') {
            modifiers.push(new EnemyVisualModifierRaidMarker(this, 0));
        }

        // Only for elite enemies
        if (this.enemy.npc !== null) {
            if (this.enemy.npc.classification_id !== 1 &&
                this.map.leafletMap.getZoom() > c.map.enemy.classification_display_zoom) {
                modifiers.push(new EnemyVisualModifierClassification(this, 1));
            }

            // Truesight marker
            if (this.enemy.npc.truesight === 1) {
                modifiers.push(new EnemyVisualModifierTruesight(this, 2));
            }
        }
        return modifiers;
    }

    /**
     * Constructs the structure for the visuals and re-fetches the main visual's and modifier's data to re-apply to
     * the interface.
     * @private
     */
    _buildVisual() {
        console.assert(this instanceof EnemyVisual, this, 'this is not an EnemyVisual');

        // Determine which modifiers the visual should have

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

            // Build modifiers object
            data.modifiers = [];
            // Fetch the modifiers we're displaying on our visual
            let modifiers = this._createModifiers();
            for (let i = 0; i < modifiers.length; i++) {
                data.modifiers.push(modifiers[i]._getTemplateData(width, height, margin));
            }

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

        switch (name) {
            case 'npc_class':
                this.mainVisual = new EnemyVisualMainEnemyClass(this);
                break;
            case 'npc_type':
                this.mainVisual = new EnemyVisualMainNpcType(this);
                break;
            case 'enemy_forces':
                this.mainVisual = new EnemyVisualMainEnemyForces(this);
                break;
        }

        this._buildVisual();
    }
}