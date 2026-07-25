/**
 * Shows the total enemy forces present on a floor as a small pill/banner.
 *
 * Whenever the map only shows a single floor worth of enemies - the split-floors ("Blizzard") layout,
 * or a facade (MDT-style combined image) that merges just one real floor - a single pill is pinned to
 * the top-center of the visible map area. When the facade merges multiple floors they are all visible
 * on the one image at once, so one floating pill is rendered per floor instead, anchored at the
 * centroid of that floor's enemies (which are already projected into facade coordinates).
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
        // Only set in facade mode - the layer group holding the per-floor pill markers.
        this._facadeMarkers = null;
        this.statusbar = null;

        // The value is derived from the number style setting and from teeming/shrouded (both the floor
        // total and the required denominator move with teeming), so recompute when either changes.
        getState().register('mapnumberstyle:changed', this, function () {
            self.refreshUI();
        });
        getState().getMapContext().register('teeming:changed', this, function () {
            self.refreshUI();
        });
        // On initial page load the control may be added before the enemies have finished loading into
        // their map object group, which would make the first sum read 0 - and, in facade mode, hide how
        // many floors are actually merged. Refresh once they're loaded.
        this.map.register('map:mapobjectgroupsloaded', this, function () {
            self.refreshUI();
        });

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_enemy_forces_floor_pill'];

                self.statusbar = $(template({value: ''}))[0];

                // The map fills the viewport underneath the site header, so the top of the Leaflet
                // container is not the top of the *visible* map. These classes offset the pill down to
                // where the map's sidebars start.
                $(self.statusbar).addClass('map_enemy_forces_floor_pill_top_center');
                if (self.map.options.embed) {
                    $(self.statusbar).addClass('embed');
                } else if (isMobile()) {
                    $(self.statusbar).addClass('mobile');
                }

                self.refreshUI();

                return self.statusbar;
            }
        };
    }

    /**
     * Refreshes the UI to reflect the current per-floor enemy forces.
     */
    refreshUI() {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        // Collect the facade-space positions of every counted enemy, grouped by their real floor. In
        // facade mode this doubles as the count of floors the facade actually merges.
        let latLngsByFloorId = this._getEnemyLatLngsByFloorId();
        let floorIds = Object.keys(latLngsByFloorId);

        // A facade that merges a single floor is just a zoomed-in view of that floor - anchoring the
        // pill on the enemies would drop it in the middle of the map for no reason, so treat it the
        // same as the split-floors layout and pin it to the top instead.
        if (this._facadeMarkers !== null && floorIds.length > 1) {
            this._renderPerFloorMarkers(latLngsByFloorId);

            return;
        }

        // In facade mode the current floor is the facade floor itself, which no enemy belongs to, so
        // take the only floor that has enemies on it instead.
        let floorId = this._facadeMarkers !== null
            ? parseInt(floorIds[0], 10)
            : getState().getCurrentFloor().id;

        this._renderStatusbar(floorId);
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
     * (Re)builds the single top-center pill for the given floor, hiding it when that floor holds no
     * enemy forces at all.
     * @param floorId {Number}
     * @private
     */
    _renderStatusbar(floorId) {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        // The facade markers and the statusbar are mutually exclusive - clear whichever isn't in use.
        if (this._facadeMarkers !== null) {
            this._facadeMarkers.clearLayers();
        }

        if (this.statusbar === null) {
            return;
        }

        let floorEnemyForces = isNaN(floorId) ? 0 : this.map.enemyForcesManager.getEnemyForcesForFloor(floorId);

        // Don't show an empty pill on floors that have no enemy forces to speak of.
        $(this.statusbar).toggleClass('d-none', floorEnemyForces <= 0);
        $(this.statusbar).find('.map_enemy_forces_floor_pill_value').html(this._formatValue(floorEnemyForces));
    }

    /**
     * (Re)builds the per-floor pill markers for a facade that merges multiple floors.
     * @param latLngsByFloorId {Object} A map of floor id to an array of [lat, lng] pairs.
     * @private
     */
    _renderPerFloorMarkers(latLngsByFloorId) {
        console.assert(this instanceof FloorEnemyForcesControls, 'this is not FloorEnemyForcesControls', this);

        // The facade markers and the statusbar are mutually exclusive - clear whichever isn't in use.
        if (this.statusbar !== null) {
            $(this.statusbar).addClass('d-none');
        }

        this._facadeMarkers.clearLayers();

        let template = Handlebars.templates['map_enemy_forces_floor_pill'];

        for (let floorId in latLngsByFloorId) {
            let floorEnemyForces = this.map.enemyForcesManager.getEnemyForcesForFloor(parseInt(floorId, 10));

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

        // Both display modes are built up front: whether a facade merges one floor or several is only
        // known once the enemies have loaded, which happens after this control is added.
        if (getState().isCurrentDungeonFacadeEnabled()) {
            this._facadeMarkers = L.layerGroup().addTo(this.map.leafletMap);
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

        this.statusbar = null;

        getState().unregister('mapnumberstyle:changed', this);
        getState().getMapContext().unregister('teeming:changed', this);
        this.map.unregister('map:mapobjectgroupsloaded', this);
    }
}
