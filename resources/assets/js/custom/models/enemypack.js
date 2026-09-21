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
/**
 * @property {Number} floor_id
 * @property {Number|null} group
 * @property {String} label
 * @property {Object} polyline
 */
class EnemyPack extends Polyline {
    constructor(map, layer) {
        super(map, layer, {name: 'enemypack', has_route_model_binding: true});

        this.group = null;
        this.label = 'Enemy pack';

        this.rawEnemies = [];

        getState().register('killzonesnumberstyle:changed', this, this.rebindTooltip.bind(this));
    }

    /**
     *
     * @returns {string}
     * @protected
     */
    _getPolylineColorDefault() {
        return c.map.enemypack.defaultColor();
    }

    /**
     * @inheritDoc
     */
    _getPolylineWeightDefault() {
        return c.map.enemypack.polygonOptions.weight;
    }

    /**
     * Outside the mapping editor a pack is drawn as the hull of its enemies, so its own weight is never shown.
     * @inheritDoc
     */
    _isWeightEditable() {
        return false;
    }

    /**
     * @inheritDoc
     */
    _isAnimatable() {
        return false;
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
                name: 'group',
                type: 'int',
                default: null
            }),
            new Attribute({
                name: 'label',
                type: 'text',
                edit: false, // Not directly changeable by user
                default: 'Enemy pack'
            }),
            new Attribute({
                name: 'mark_as_skippable',
                type: 'button',
                buttonType: 'info',
                buttonText: lang.get('js.enemypack_mark_as_skippable_button_text_label'),
                clicked: function (e) {
                    self.map.leafletMap.closePopup();

                    let enemyMapObjectGroup = self.map.mapObjectGroupManager.getEnemyMapObjectGroup();

                    for (let key in enemyMapObjectGroup.objects) {
                        let enemy = enemyMapObjectGroup.objects[key];

                        // Detach all enemies from this pack if it's deleted
                        if (enemy.enemy_pack_id === self.id) {
                            enemy.skippable = !enemy.skippable;
                            enemy.save();
                        }
                    }
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

        this._updateHullLayer();
    }

    /**
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // The nested polyline is loaded through this same method; only the pack itself carries its enemies
        if (parentAttribute === null && !(getState().getMapContext() instanceof MapContextMappingVersionEdit)) {
            // Re-set the layer now that we know of the raw enemies
            this.setRawEnemies(remoteMapObject.enemies);
            this._updateHullLayer();
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
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getEnemyMapObjectGroup();
        for (let i = 0; i < this.rawEnemies.length; i++) {
            let rawEnemy = this.rawEnemies[i];
            let enemy = enemyMapObjectGroup.findMapObjectById(rawEnemy.id);

            if (enemy !== null) {
                // We're not unregging this since this will never change when in view/edit mode, only in admin mode when this code isn't triggered
                enemy.register(['shown', 'hidden'], this, this._onEnemyVisibilityToggled.bind(this));
                // Ensure that the tooltip now shows the group number
                enemy.bindTooltip();
            } else {
                console.warn(`Unable to find enemy with id ${rawEnemy.id} for enemy pack ${this.id}`);
            }
        }
    }

    /**
     * Creates a new layer ready to be assigned somewhere.
     * @returns {L.Layer|null}
     */
    _updateHullLayer() {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

        let self = this;

        // Convert raw enemies to current enemies
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getEnemyMapObjectGroup();
        let latLngs = [];
        for (let i = 0; i < this.rawEnemies.length; i++) {
            let rawEnemy = this.rawEnemies[i];
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(rawEnemy.id);

            if (enemy !== null && enemy.layer !== null && enemy.shouldBeVisible()) {
                let enemyLatLng = enemy.layer.getLatLng();
                latLngs.push([enemyLatLng.lat, enemyLatLng.lng]);
            }
        }

        let floor = getState().getMapContext().getFloorById(this.floor_id);
        let enemyPackMargin = (floor !== false && floor.enemy_pack_margin !== null && floor.enemy_pack_margin !== undefined) ?
            floor.enemy_pack_margin : c.map.enemypack.margin;

        let result = createOffsetHullPolygon(latLngs, enemyPackMargin, c.map.enemypack.arcSegments, c.map.enemypack.polygonOptions);
        if (result !== null) {
            result.on('click', function (clickEvent) {
                self.signal('enemypack:clicked', {clickEvent: clickEvent});
            });
        }

        let enemyPackMapObjectGroup = this.map.mapObjectGroupManager.getEnemyPackMapObjectGroup();
        enemyPackMapObjectGroup.setLayerToMapObject(result, this);
        this.rebindTooltip();
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

    bindTooltip() {
        super.bindTooltip();

        if (this.layer !== null) {
            let displayText = '';

            if (typeof this.group !== 'undefined' && this.group !== null) {
                displayText += `G${this.group}: `;
            }

            displayText += `+${this.getEnemyForces()} / +${getFormattedPercentage(this.getEnemyForces(), this.map.enemyForcesManager.getEnemyForcesRequired())}%`;

            this.layer.bindTooltip(displayText.trim(), {
                sticky: true,
                direction: 'top'
            });
        }
    }

    /**
     *
     * @returns {number}
     */
    getEnemyForces() {
        let result = 0;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getEnemyMapObjectGroup();
        for (let i = 0; i < this.rawEnemies.length; i++) {
            let rawEnemy = this.rawEnemies[i];
            let enemy = enemyMapObjectGroup.findMapObjectById(rawEnemy.id);
            result += enemy.getEnemyForces();
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
        getState().unregister('killzonesnumberstyle:changed', this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyPack,
    };
}
