class EnemyVisualControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;
        this.map.register('map:refresh', this, function () {
            refreshSelectPickers();
        });

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_enemy_visuals_template'];

                let data = $.extend({
                        beguiling_presets: self.map.options.beguilingPresets
                    }, getHandlebarsDefaultVariables()
                );

                // Build the status bar from the template
                self.domElement = $(template(data));
                let $domElement = $(self.domElement);
                $domElement.find('#map_enemy_visuals_dropdown').bind('change', self._enemyVisualChanged.bind(self));
                $domElement.find('#map_enemy_beguiling_preset_dropdown').bind('change', self._beguilingPresetChanged.bind(self));
                $domElement.find('#map_enemy_visuals_map_mdt_clones_to_enemies').bind('change', self._mdtEnemyMappingChanged.bind(self));

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

        let mdtEnemiesEnabled = $('#map_enemy_visuals_map_mdt_clones_to_enemies').is(":checked");

        // Hide or show any MDT enemies
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (index, value) {
            if (value.is_mdt) {
                enemyMapObjectGroup.setMapObjectVisibility(value, mdtEnemiesEnabled);
            }
        });
    }

    /**
     * Called whenever the user wants to change enemy visuals.
     * @param changedEvent
     * @private
     */
    _enemyVisualChanged(changedEvent) {
        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        let visualType = $('#map_enemy_visuals_dropdown').val();

        // Keep track of the visual type
        this.map.setVisualType(visualType);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            console.assert(enemy instanceof Enemy, this, 'enemy is not an Enemy');
            enemy.visual.setVisualType(visualType);
        });
    }

    /**
     * Called whenever the user wants to change beguiling preset.
     * @param changedEvent
     * @private
     */
    _beguilingPresetChanged(changedEvent) {
        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        let beguilingPreset = parseInt($('#map_enemy_beguiling_preset_dropdown').val());

        // Keep track of the visual type
        this.map.setBeguilingPreset(beguilingPreset);
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

        // Restore what the user had selected
        $('#map_enemy_visuals_dropdown').val(this.map.getVisualType());

        refreshSelectPickers();
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        this.map.unregister('map:refresh', this);
    }
}
