// Used for the cursor preview while placing (and briefly for the layer before refreshPill() swaps
// in the real pill icon). Deliberately a static glyph, not the pill markup: the pill's content
// (enemy forces need before entering) isn't known until enemies are assigned to the checkpoint.
let LeafletEnemyForcesCheckpointIcon = new L.divIcon({
    html: '<i class="fas fa-percent"></i>',
    iconSize: [30, 30],
    className: 'marker_div_icon_font_awesome map_enemy_forces_checkpoint_marker_icon',
});

let LeafletEnemyForcesCheckpointMarker = L.Marker.extend({
    options: {
        icon: LeafletEnemyForcesCheckpointIcon,
    },
});

L.Draw.EnemyForcesCheckpoint = L.Draw.Marker.extend({
    statics: {
        TYPE: 'enemyforcescheckpoint',
    },
    options: {
        icon: LeafletEnemyForcesCheckpointIcon,
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.EnemyForcesCheckpoint.TYPE;

        L.Draw.Feature.prototype.initialize.call(this, map, options);
    },
});

/**
 * A mapper-defined group of enemies - typically a corridor - showing how much enemy forces you must
 * already have before entering it.
 *
 * The group may span floors, but the checkpoint itself is anchored on one. On any other floor its
 * enemies live on, a satellite pill is drawn at the centroid of that floor's members instead, so the
 * information isn't invisible on exactly the floors it matters for. In the facade layout every enemy
 * has been moved onto the facade floor along with the anchor, so no satellites are ever needed.
 *
 * @property {String} name
 */
class EnemyForcesCheckpoint extends VersionableMapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'enemyforcescheckpoint', has_route_model_binding: true});

        let self = this;

        this.label = 'Enemy Forces Checkpoint';

        // Satellite pills for floors this checkpoint has enemies on, but isn't anchored to.
        this._satelliteLayerGroup = null;

        // The value depends on the enemies (which load after the map objects are created), on the
        // number style setting, and on teeming - both the checkpoint's total and the required denominator
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
        // The satellite pill (and, in the admin subclass, the connection lines) are drawn outside the
        // MapObjectGroup's own layer group, so hiding "Enemy forces checkpoints" in the Map Elements
        // dropdown would never reach them. Redraw on every toggle - the draw itself is gated on
        // isMapObjectGroupShown(), so a hidden group draws nothing.
        let mapObjectGroup = this.getMapObjectGroup();
        if (mapObjectGroup !== false) {
            mapObjectGroup.register('visibility:changed', this, function () {
                self.refreshPill();
            });
        }
    }

    /**
     * The MapObjectGroup that owns this checkpoint, or false when there is none.
     * @returns {MapObjectGroup|Boolean}
     */
    getMapObjectGroup() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        return this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_FORCES_CHECKPOINT);
    }

    /**
     * Whether the map object group owning this checkpoint is currently shown. Anything drawn outside that
     * group's layer group has to consult this itself: MapObjectGroup.setVisibility(false) only ever removes
     * each map object's own `layer`, so it cannot know about the extra layers we add to the map.
     * @returns {Boolean}
     */
    isMapObjectGroupShown() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        let mapObjectGroup = this.getMapObjectGroup();

        return mapObjectGroup === false || mapObjectGroup.isShown();
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this was not an EnemyForcesCheckpoint', this);

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
     * All enemies that were assigned to this checkpoint, across every floor.
     * @returns {Enemy[]}
     */
    getEnemies() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        let result = [];

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        // May be false in an admin setting where there's no enemies
        if (enemyMapObjectGroup === false) {
            return result;
        }

        for (let key in enemyMapObjectGroup.objects) {
            let enemy = enemyMapObjectGroup.objects[key];

            if (enemy.enemy_forces_checkpoint_id === this.id) {
                result.push(enemy);
            }
        }

        return result;
    }

    /**
     * The total enemy forces of this checkpoint's enemies.
     * @returns {Number}
     */
    getEnemyForces() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        return this.map.enemyForcesManager.getEnemyForcesForEnemies(this.getEnemies());
    }

    /**
     * The floors this checkpoint's enemies actually live on. In the facade layout floor_id has been
     * replaced by the facade floor, so source_floor_id - shipped for exactly this reason - is used
     * where present.
     * @returns {Number[]}
     */
    getFloorIds() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

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
     * checkpoint is anchored to, the satellite pill for that floor.
     */
    refreshPill() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        let html = this._getPillHtml();

        if (this.layer !== null) {
            this.layer.setIcon(L.divIcon({
                className: 'map_enemy_forces_checkpoint_pill_icon',
                iconSize: null,
                html: html,
            }));
        }

        this._refreshSatellitePill(html);
        this.rebindTooltip();
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);
        super.onLayerInit();

        this.refreshPill();
    }

    /**
     * The pill itself only says how much you need before entering. The checkpoint's name and what it
     * actually holds - the numbers that make that figure interpretable - go in the tooltip.
     * @inheritDoc
     */
    bindTooltip() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);
        super.bindTooltip();

        if (this.map.options.noUI || this.layer === null) {
            return;
        }

        let name = this.name === null || this.name === '' ? lang.get('js.enemy_forces_checkpoint_unnamed_label') : this.name;
        let enemyForces = this.getEnemyForces();

        let tooltipText;
        if (getState().getMapNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            tooltipText = lang.get('js.enemy_forces_checkpoint_tooltip_enemy_forces', {name: name, enemyForces: enemyForces});
        } else {
            tooltipText = lang.get('js.enemy_forces_checkpoint_tooltip_percentage', {
                name: name,
                percentage: getFormattedPercentage(enemyForces, this.map.enemyForcesManager.getEnemyForcesRequired()),
            });
        }

        let floorIds = this.getFloorIds();
        if (floorIds.length > 1) {
            tooltipText += ` ${lang.get('js.enemy_forces_checkpoint_tooltip_spans_floors', {floors: floorIds.length})}`;
        }

        this.layer.bindTooltip(tooltipText, {direction: 'top'});
    }

    /**
     * @inheritDoc
     */
    cleanup() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);
        super.cleanup();

        this._removeSatellitePill();

        this.map.unregister('map:mapobjectgroupsloaded', this);
        getState().unregister('mapnumberstyle:changed', this);
        getState().getMapContext().unregister('teeming:changed', this);
        getState().unregister('floorid:changed', this);

        let mapObjectGroup = this.getMapObjectGroup();
        if (mapObjectGroup !== false) {
            mapObjectGroup.unregister('visibility:changed', this);
        }
    }

    /**
     * Builds the pill's markup: how much enemy forces you need before entering this checkpoint, following
     * the "Enemy number style" map setting.
     * @returns {String}
     * @private
     */
    _getPillHtml() {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        let enemyForces = this.getEnemyForces();
        let enemyForcesRequired = this.map.enemyForcesManager.getEnemyForcesRequired();
        // Everything that is NOT in this checkpoint - what you must already have killed when you walk in.
        let requiredBefore = Math.max(0, enemyForcesRequired - enemyForces);

        let value;
        if (getState().getMapNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            value = lang.get('js.enemy_forces_checkpoint_pill_enemy_forces', {enemyForces: requiredBefore});
        } else {
            value = lang.get('js.enemy_forces_checkpoint_pill_percentage', {
                percentage: getFormattedPercentage(requiredBefore, enemyForcesRequired),
            });
        }

        return Handlebars.templates['map_enemy_forces_checkpoint_pill']({value: value});
    }

    /**
     * Draws (or removes) the pill for a floor this checkpoint has enemies on but isn't anchored to.
     * @param html {String}
     * @private
     */
    _refreshSatellitePill(html) {
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        this._removeSatellitePill();

        // The user hid this map object group - the satellite must obey that too.
        if (!this.isMapObjectGroupShown()) {
            return;
        }

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
                className: 'map_enemy_forces_checkpoint_pill_icon',
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
        console.assert(this instanceof EnemyForcesCheckpoint, 'this is not an EnemyForcesCheckpoint', this);

        if (this._satelliteLayerGroup !== null) {
            this.map.leafletMap.removeLayer(this._satelliteLayerGroup);
            this._satelliteLayerGroup = null;
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyForcesCheckpoint,
    };
}
