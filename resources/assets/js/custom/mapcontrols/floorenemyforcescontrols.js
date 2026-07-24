/**
 * Shows the total enemy forces present on a floor as a small pill/banner.
 *
 * In the split-floors ("Blizzard") layout this is a single screen-fixed pill at the top-center of
 * the map, reflecting the currently-visible floor. In the facade (MDT-style combined image) layout
 * it renders one floating pill per floor union, anchored at the centroid of that union's area, since
 * every floor is visible on the one image at once.
 *
 * The label follows the "Enemy number style" map setting: an absolute enemy forces count, or a
 * percentage of the enemy forces required to complete the dungeon.
 */
class FloorEnemyForcesControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        let self = this;

        this.map = map;
        // Only set in facade mode - the layer group holding the per-union pill markers.
        this._facadeMarkers = null;

        // The value is derived from the number style setting and from teeming/shrouded (both the floor
        // total and the required denominator move with teeming), so recompute when either changes.
        getState().register('mapnumberstyle:changed', this, function () {
            self.refreshUI();
        });
        getState().getMapContext().register('teeming:changed', this, function () {
            self.refreshUI();
        });
        // On initial page load the control may be added before the enemies have finished loading into
        // their map object group, which would make the first sum read 0. Refresh once they're loaded.
        this.map.register('map:mapobjectgroupsloaded', this, function () {
            self.refreshUI();
        });

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_enemy_forces_floor_pill'];

                self.statusbar = $(template({value: ''}))[0];

                self.refreshUI();

                return self.statusbar;
            }
        };
    }

    /**
     * Builds the pill's display value for a floor's total enemy forces, following the number style setting.
     * @param floorEnemyForces {Number}
     * @returns {String}
     * @private
     */
    _formatValue(floorEnemyForces) {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        if (getState().getMapNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            return lang.get('js.enemy_forces_floor_pill_enemy_forces', {enemyForces: floorEnemyForces});
        }

        let percent = getFormattedPercentage(floorEnemyForces, this.map.enemyForcesManager.getEnemyForcesRequired());

        return lang.get('js.enemy_forces_floor_pill_percentage', {percentage: percent});
    }

    /**
     * Refreshes the UI to reflect the current per-floor enemy forces.
     */
    refreshUI() {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        if (getState().isCurrentDungeonFacadeEnabled()) {
            this._renderFacadeMarkers();
        } else if (typeof this.statusbar !== 'undefined') {
            let floorEnemyForces = this.map.enemyForcesManager.getEnemyForcesForFloor(getState().getCurrentFloor().id);
            $(this.statusbar).find('.map_enemy_forces_floor_pill_value').html(this._formatValue(floorEnemyForces));
        }
    }

    /**
     * (Re)builds the per-union pill markers for the facade layout.
     * @private
     */
    _renderFacadeMarkers() {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        if (this._facadeMarkers === null) {
            return;
        }

        this._facadeMarkers.clearLayers();

        let template = Handlebars.templates['map_enemy_forces_floor_pill'];
        let mapContext = getState().getMapContext();
        let floorUnions = mapContext.getFloorUnions();
        let floorUnionAreas = mapContext.getFloorUnionAreas();

        for (let i = 0; i < floorUnions.length; i++) {
            let floorUnion = floorUnions[i];
            let floorEnemyForces = this.map.enemyForcesManager.getEnemyForcesForFloor(floorUnion.target_floor_id);

            // Don't clutter the map with pills for floors that have no enemy forces.
            if (floorEnemyForces <= 0) {
                continue;
            }

            let html = template({value: this._formatValue(floorEnemyForces)});

            L.marker(this._getFloorUnionAnchor(floorUnion, floorUnionAreas), {
                icon: L.divIcon({
                    html: html,
                    className: 'map_enemy_forces_floor_pill_icon',
                    // The pill is sized by its content; a fixed iconSize would clip or mis-center it.
                    iconSize: null,
                }),
                // Purely informational - never intercept clicks meant for enemies underneath.
                interactive: false,
                keyboard: false,
            }).addTo(this._facadeMarkers);
        }
    }

    /**
     * Resolves the facade-space anchor for a floor union's pill: the centroid of its area polygon(s),
     * falling back to the union's own marker position when no area vertices are available.
     * @param floorUnion {Object}
     * @param floorUnionAreas {Array}
     * @returns {Object} An L.latLng.
     * @private
     */
    _getFloorUnionAnchor(floorUnion, floorUnionAreas) {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        let points = [];
        for (let i = 0; i < floorUnionAreas.length; i++) {
            let floorUnionArea = floorUnionAreas[i];
            if (floorUnionArea.floor_union_id !== floorUnion.id || typeof floorUnionArea.vertices_json === 'undefined') {
                continue;
            }

            let vertices = JSON.parse(floorUnionArea.vertices_json);
            for (let j = 0; j < vertices.length; j++) {
                points.push([vertices[j].lat, vertices[j].lng]);
            }
        }

        if (points.length > 0) {
            return getCenteroid(points);
        }

        return L.latLng(floorUnion.lat, floorUnion.lng);
    }

    /**
     * Adds the Control to the current LeafletMap.
     */
    addControl() {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        if (getState().isCurrentDungeonFacadeEnabled()) {
            this._facadeMarkers = L.layerGroup().addTo(this.map.leafletMap);
            this._renderFacadeMarkers();

            return;
        }

        // Code for the statusbar
        L.Control.Statusbar = L.Control.extend(this.mapControlOptions);

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        this._mapControl = L.control.statusbar({position: 'tophorizontalcenter'}).addTo(this.map.leafletMap);

        // Fix for Edge prioritizing float: left; from leaflet-control, leading to the div having 1 pixel
        // width rather than fitting its content. Removing the leaflet-control class fixes this.
        $(this.statusbar).removeClass('leaflet-control');

        this.refreshUI();
    }

    cleanup() {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);
        super.cleanup();

        if (this._facadeMarkers !== null) {
            this.map.leafletMap.removeLayer(this._facadeMarkers);
            this._facadeMarkers = null;
        }

        getState().unregister('mapnumberstyle:changed', this);
        getState().getMapContext().unregister('teeming:changed', this);
        this.map.unregister('map:mapobjectgroupsloaded', this);
    }
}
