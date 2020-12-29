class EnemyVisualControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        // When we or someone else changed the enemy display type
        getState().register('enemydisplaytype:changed', this, function (changedEvent) {
            let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                console.assert(enemy instanceof Enemy, 'enemy is not an Enemy', self);
                if (enemy.visual !== null) {
                    enemy.visual.setVisualType(changedEvent.data.enemyDisplayType);
                }
            });
        });

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_enemy_visuals_template'];

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                        enemy_visual_type: self.map.options.defaultEnemyVisualType
                    }
                );

                // Build the status bar from the template
                self.domElement = $(template(data));
                let $domElement = $(self.domElement);

                self.domElement = self.domElement[0];

                return self.domElement;
            }
        };
    }

    /**
     * Called whenever the MDT enemy mapping checkbox' value has changed.
     * @param changedEvent
     * @private
     */
    _mdtEnemyMappingChanged(changedEvent) {
        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        getState().setMdtMappingModeEnabled(
            $('#map_enemy_visuals_map_mdt_clones_to_enemies').is(':checked')
        );
    }

    /**
     * Called whenever the user wants to change enemy visuals.
     * @param changedEvent
     * @private
     */
    _enemyVisualChanged(changedEvent) {
        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        let enemyDisplayType = $('#map_enemy_visuals_dropdown').val();

        // Keep track of the visual type
        getState().setEnemyDisplayType(enemyDisplayType);
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        // Code for the domElement
        L.Control.domElement = L.Control.extend(this.mapControlOptions);

        L.control.domElement = function (opts) {
            return new L.Control.domElement(opts);
        };

        this._mapControl = L.control.domElement({position: 'topright'}).addTo(this.map.leafletMap);

        // Add the leaflet draw control to the sidebar
        let container = this._mapControl.getContainer();
        let $targetContainer = $('#map_enemy_visuals_container');
        $targetContainer.append(container);

        $('#map_enemy_visuals_dropdown').bind('change', this._enemyVisualChanged.bind(this))
        // Restore what the user had selected
            .val(getState().getEnemyDisplayType());
        $('#map_enemy_visuals_map_mdt_clones_to_enemies').bind('change', this._mdtEnemyMappingChanged.bind(this));

        // Now handled by dungeonmap refresh
        // refreshSelectPickers();
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        getState().unregister('enemydisplaytype:changed', this);
    }
}
