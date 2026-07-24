/**
 * Shows the total enemy forces present on a floor as a small pill/banner.
 *
 * In the split-floors ("Blizzard") layout this is a single screen-fixed pill at the top-center of
 * the map, reflecting the currently-visible floor. In the facade (MDT-style combined image) layout
 * it renders one floating pill per floor, anchored at the centroid of that floor's enemies (which are
 * already projected into facade coordinates), since every floor is visible on the one image at once.
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
     * (Re)builds the per-floor pill markers for the facade layout.
     * @private
     */
    _renderFacadeMarkers() {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        if (this._facadeMarkers === null) {
            return;
        }

        this._facadeMarkers.clearLayers();

        let template = Handlebars.templates['map_enemy_forces_floor_pill'];

        // Collect the facade-space positions of every counted enemy, grouped by their real floor, so
        // each floor's pill can be anchored at the centroid of its enemies on the combined image.
        let latLngsByFloorId = this._getEnemyLatLngsByFloorId();

        for (let floorId in latLngsByFloorId) {
            let floorEnemyForces = this.map.enemyForcesManager.getEnemyForcesForFloor(parseInt(floorId));

            // Don't clutter the map with pills for floors that have no enemy forces.
            if (floorEnemyForces <= 0) {
                continue;
            }

            let html = template({value: this._formatValue(floorEnemyForces)});

            L.marker(getCenteroid(latLngsByFloorId[floorId]), {
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
     * Gathers the (facade-space) lat/lngs of every counted enemy, grouped by the enemy's floor.
     * @returns {Object} A map of floor id to an array of [lat, lng] pairs.
     * @private
     */
    _getEnemyLatLngsByFloorId() {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        let latLngsByFloorId = {};

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        if (enemyMapObjectGroup === false) {
            return latLngsByFloorId;
        }

        for (let key in enemyMapObjectGroup.objects) {
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.objects[key];

            // Match the same enemies that getEnemyForcesForFloor() sums, and that have a placed layer.
            if (enemy.isObsolete() || enemy.shouldBeIgnored() || enemy.layer === null || typeof enemy.layer === 'undefined') {
                continue;
            }

            let latLng = enemy.layer.getLatLng();
            (latLngsByFloorId[enemy.floor_id] ??= []).push([latLng.lat, latLng.lng]);
        }

        return latLngsByFloorId;
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
