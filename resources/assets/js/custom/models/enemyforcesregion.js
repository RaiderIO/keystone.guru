let LeafletEnemyForcesRegionIcon = new L.divIcon({
    className: 'map_enemy_forces_region_pill_icon',
    // The pill is sized by its content; a fixed iconSize would clip or mis-center it.
    iconSize: null,
});

let LeafletEnemyForcesRegionMarker = L.Marker.extend({
    options: {
        icon: LeafletEnemyForcesRegionIcon,
    },
});

L.Draw.EnemyForcesRegion = L.Draw.Marker.extend({
    statics: {
        TYPE: 'enemyforcesregion',
    },
    options: {
        icon: LeafletEnemyForcesRegionIcon,
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.EnemyForcesRegion.TYPE;

        L.Draw.Feature.prototype.initialize.call(this, map, options);
    },
});

/**
 * A mapper-defined group of enemies - typically a corridor - showing how much enemy forces you must
 * already have before entering it.
 *
 * The group may span floors, but the region itself is anchored on one. On any other floor its
 * enemies live on, a satellite pill is drawn at the centroid of that floor's members instead, so the
 * information isn't invisible on exactly the floors it matters for. In the facade layout every enemy
 * has been moved onto the facade floor along with the anchor, so no satellites are ever needed.
 *
 * @property {String} name
 */
class EnemyForcesRegion extends VersionableMapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'enemyforcesregion', has_route_model_binding: true});

        let self = this;

        this.label = 'Enemy Forces Region';

        // Satellite pills for floors this region has enemies on, but isn't anchored to.
        this._satelliteLayerGroup = null;

        // The value depends on the enemies (which load after the map objects are created), on the
        // number style setting, and on teeming - both the region's total and the required denominator
        // move with it.
        this.map.register('map:mapobjectgroupsloaded', this, function () {
            self.refreshPill();
        });
        getState().register('mapnumberstyle:changed', this, function () {
            self.refreshPill();
        });
        getState().getMapContext().register('teeming:changed', this, function () {
            self.refreshPill();
        });
        getState().register('floorid:changed', this, function () {
            self.refreshPill();
        });
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof EnemyForcesRegion, 'this was not an EnemyForcesRegion', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id,
            }),
            new Attribute({
                name: 'name',
                type: 'string',
                edit: true,
                default: '',
            }),
            new Attribute({
                name: 'lat',
                type: 'float',
                edit: false,
                getter: () => this.layer.getLatLng().lat,
            }),
            new Attribute({
                name: 'lng',
                type: 'float',
                edit: false,
                getter: () => this.layer.getLatLng().lng,
            }),
        ]);
    }

    /**
     * All enemies that were assigned to this region, across every floor.
     * @returns {Enemy[]}
     */
    getEnemies() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);

        let result = [];

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        // May be false in an admin setting where there's no enemies
        if (enemyMapObjectGroup === false) {
            return result;
        }

        for (let key in enemyMapObjectGroup.objects) {
            let enemy = enemyMapObjectGroup.objects[key];

            if (enemy.enemy_forces_region_id === this.id) {
                result.push(enemy);
            }
        }

        return result;
    }

    /**
     * The total enemy forces of this region's enemies.
     * @returns {Number}
     */
    getEnemyForces() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);

        return this.map.enemyForcesManager.getEnemyForcesForEnemies(this.getEnemies());
    }

    /**
     * The floors this region's enemies actually live on. In the facade layout floor_id has been
     * replaced by the facade floor, so source_floor_id - shipped for exactly this reason - is used
     * where present.
     * @returns {Number[]}
     */
    getFloorIds() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);

        let result = [];

        let enemies = this.getEnemies();
        for (let index in enemies) {
            let enemy = enemies[index];
            let floorId = enemy.source_floor_id ?? enemy.floor_id;

            if (!result.includes(floorId)) {
                result.push(floorId);
            }
        }

        return result;
    }

    /**
     * Rebuilds the pill label and, when the current floor holds members but isn't the floor this
     * region is anchored to, the satellite pill for that floor.
     */
    refreshPill() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);

        let html = this._getPillHtml();

        if (this.layer !== null) {
            this.layer.setIcon(L.divIcon({
                className: 'map_enemy_forces_region_pill_icon',
                iconSize: null,
                html: html,
            }));
        }

        this._refreshSatellitePill(html);
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);
        super.onLayerInit();

        this.refreshPill();
    }

    /**
     * @inheritDoc
     */
    cleanup() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);
        super.cleanup();

        this._removeSatellitePill();

        this.map.unregister('map:mapobjectgroupsloaded', this);
        getState().unregister('mapnumberstyle:changed', this);
        getState().getMapContext().unregister('teeming:changed', this);
        getState().unregister('floorid:changed', this);
    }

    /**
     * Builds the pill's markup: how much enemy forces you need before entering this region, following
     * the "Enemy number style" map setting.
     * @returns {String}
     * @private
     */
    _getPillHtml() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);

        let enemyForces = this.getEnemyForces();
        let enemyForcesRequired = this.map.enemyForcesManager.getEnemyForcesRequired();
        // Everything that is NOT in this region - what you must already have killed when you walk in.
        let requiredBefore = Math.max(0, enemyForcesRequired - enemyForces);

        let value;
        if (getState().getMapNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            value = lang.get('js.enemy_forces_region_pill_enemy_forces', {enemyForces: requiredBefore});
        } else {
            value = lang.get('js.enemy_forces_region_pill_percentage', {
                percentage: getFormattedPercentage(requiredBefore, enemyForcesRequired),
            });
        }

        return Handlebars.templates['map_enemy_forces_region_pill']({value: value});
    }

    /**
     * Draws (or removes) the pill for a floor this region has enemies on but isn't anchored to.
     * @param html {String}
     * @private
     */
    _refreshSatellitePill(html) {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);

        this._removeSatellitePill();

        let currentFloorId = getState().getCurrentFloor().id;

        // The anchor itself covers this floor already.
        if (currentFloorId === this.floor_id) {
            return;
        }

        let latLngs = [];
        let enemies = this.getEnemies();
        for (let index in enemies) {
            let enemy = enemies[index];

            if ((enemy.source_floor_id ?? enemy.floor_id) === currentFloorId) {
                latLngs.push({lat: enemy.lat, lng: enemy.lng});
            }
        }

        if (latLngs.length === 0) {
            return;
        }

        let lat = 0, lng = 0;
        for (let index in latLngs) {
            lat += latLngs[index].lat;
            lng += latLngs[index].lng;
        }

        this._satelliteLayerGroup = L.layerGroup().addTo(this.map.leafletMap);

        L.marker([lat / latLngs.length, lng / latLngs.length], {
            icon: L.divIcon({
                className: 'map_enemy_forces_region_pill_icon',
                iconSize: null,
                html: html,
            }),
            // Purely informational - never intercept clicks meant for enemies underneath.
            interactive: false,
            keyboard: false,
        }).addTo(this._satelliteLayerGroup);
    }

    /**
     * @private
     */
    _removeSatellitePill() {
        console.assert(this instanceof EnemyForcesRegion, 'this is not an EnemyForcesRegion', this);

        if (this._satelliteLayerGroup !== null) {
            this.map.leafletMap.removeLayer(this._satelliteLayerGroup);
            this._satelliteLayerGroup = null;
        }
    }
}
