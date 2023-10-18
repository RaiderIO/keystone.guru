let LeafletIconFloorUnion = L.divIcon({
    html: '<i class="fas fa-vector-square"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown'
});

// $(function () {
L.Draw.FloorUnion = L.Draw.Marker.extend({
    statics: {
        TYPE: 'floorunion'
    },
    options: {
        icon: LeafletIconFloorUnion
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.FloorUnion.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

/**
 * @property {Number} target_floor_id
 * @property {Number} size
 * @property {Number} rotation
 */
class FloorUnion extends Icon {
    constructor(map, layer) {
        super(map, layer, {name: 'floor_union', route_suffix: 'floorunion', hasRouteModelBinding: true});

        this.label = 'FloorUnion';
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof FloorUnion, 'this is not a FloorUnion', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).concat([

            new Attribute({
                name: 'target_floor_id',
                type: 'select',
                values: function () {
                    // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
                    return getState().getMapContext().getFloorSelectValues(self.floor_id);
                },
                default: null
            }),
            new Attribute({
                name: 'size',
                type: 'float',
                default: 10
            }),
            new Attribute({
                name: 'rotation',
                type: 'float',
                default: 0
            }),
        ]);
    }

    /**
     * @inheritDoc
     */
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // When in admin mode, show all map icons
        if (!getState().isMapAdmin()) {
            // Hide this enemy by default
            this.setDefaultVisible(false);
        }
    }

    /**
     * @inheritDoc
     */
    isDeletable() {
        return this.isEditable();
    }

    toString() {
        return `Floor union-(${this.id})`;
    }
}
