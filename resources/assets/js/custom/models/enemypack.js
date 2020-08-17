// $(function () {
    L.Draw.EnemyPack = L.Draw.Polygon.extend({
        statics: {
            TYPE: 'enemypack'
        },
        options: {},
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.EnemyPack.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
// });

class EnemyPack extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'enemypack'});

        this.label = 'Enemy pack';

        this.color = null;
        this.rawEnemies = [];
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof EnemyPack, 'this was not an EnemyPack', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            new Attribute({
                name: 'label',
                type: 'text',
                edit: false, // Not directly changeable by user
                default: 'Enemy pack'
            }),
            new Attribute({
                name: 'vertices',
                type: 'array',
                edit: false,
                getter: function () {
                    return self.getVertices();
                }
            })
        ]);
    }

    /**
     *
     * @param triggeredEvent
     * @private
     */
    _onEnemyVisibilityToggled(triggeredEvent) {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

        if (!this.map.isRefreshingMap()) {
            let newLayer = this.createHullLayer();

            // Refresh our enemy pack to neatly fit around the visible enemies
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PACK);
            enemyMapObjectGroup.setLayerToMapObject(newLayer, this);
        }
    }

    /**
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // Only called when not in admin state
        if (getState().getMapContext() instanceof MapContextDungeonRoute) {
            // Re-set the layer now that we know of the raw enemies
            this.setRawEnemies(remoteMapObject.enemies);
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PACK);
            enemyMapObjectGroup.setLayerToMapObject(this.createHullLayer(), this);
        }
    }

    isEditableByPopup() {
        return false;
    }

    /**
     * Sets the raw enemies.
     * @param rawEnemies
     */
    setRawEnemies(rawEnemies) {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);
        this.rawEnemies = rawEnemies;
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < this.rawEnemies.length; i++) {
            let rawEnemy = this.rawEnemies[i];
            let enemy = enemyMapObjectGroup.findMapObjectById(rawEnemy.id);

            // We're not unregging this since this will never change when in view/edit mode, only in admin mode when this code isn't triggered
            enemy.register(['shown', 'hidden'], this, this._onEnemyVisibilityToggled.bind(this));
        }
    }

    /**
     * Creates a new layer ready to be assigned somewhere.
     * @returns {L.Layer|null}
     */
    createHullLayer() {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

        let result = null;

        // Convert raw enemies to current enemies
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        let latLngs = [];
        for (let i = 0; i < this.rawEnemies.length; i++) {
            let rawEnemy = this.rawEnemies[i];
            let enemy = enemyMapObjectGroup.findMapObjectById(rawEnemy.id);

            if (enemy !== null && enemy.layer !== null) {
                let enemyLatLng = enemy.layer.getLatLng();
                latLngs.push([enemyLatLng.lat, enemyLatLng.lng]);
            }
        }

        // Build a layer based off a hull if we're supposed to
        if (latLngs.length > 1) {
            let hullPoints = hull(latLngs, 100);
            // Only if we can actually make an offset
            if (hullPoints.length > 1) {
                try {
                    hullPoints = (new Offset()).data(hullPoints).arcSegments(c.map.enemypack.arcSegments(hullPoints.length)).margin(c.map.enemypack.margin);

                    result = L.polygon(hullPoints, c.map.enemypack.polygonOptions);
                } catch (error) {
                    // Not particularly interesting to spam the console with
                    // console.error('Unable to create offset for pack', remoteMapObject.id, error);
                }
            }
        }


        if (result === null) {
            console.warn(`Unable to create hull layer for enemypack ${this.id}; not enough data points`);
        }

        // May be null
        return result;
    }

    /**
     * Rebuild the decorators for this route (directional arrows etc).
     * @private
     */
    // _getDecorator() {
    //     console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

    // Not sure if this really adds anything but I'll keep it here in case I want to do something with it
    // this._cleanDecorator();
    //
    // this.decorator = L.polylineDecorator(this.layer, {
    //     patterns: [
    //         {
    //             offset: 12,
    //             repeat: 25,
    //             symbol: L.Symbol.dash({
    //                 pixelSize: 10,
    //                 pathOptions: {color: 'darkred', weight: 2}
    //             })
    //         }
    //     ]
    // });
    // this.decorator.addTo(this.map.leafletMap);
    // }

    // To be overridden by any implementing classes
    onLayerInit() {
        // this.constructor.name.indexOf('EnemyPack') >= 0
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);
        super.onLayerInit();

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }

    getVertices() {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

        let coordinates = this.layer.toGeoJSON().geometry.coordinates[0];
        let result = [];
        for (let i = 0; i < coordinates.length - 1; i++) {
            result.push({lat: coordinates[i][1], lng: coordinates[i][0]});
        }
        return result;
    }

    toString() {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

        return 'Enemy pack-' + this.id;
    }

    cleanup() {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

        super.cleanup();
    }
}