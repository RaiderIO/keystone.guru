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
        super(map, layer, {name: 'floorunion', route_suffix: 'floorunion', hasRouteModelBinding: true});

        this.label = 'FloorUnion';
        this.comment = '';
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

        return this._cachedAttributes = super._getAttributes(force).filter((attribute) => {
            return !['faction', 'teeming', 'map_icon_type_id', 'comment'].includes(attribute.options.name);
        }).concat([
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

    _getDecorator() {
        if (typeof this.lat === 'undefined' || typeof this.lng === 'undefined') {
            return null;
        }

        let aspectRatio = 1.5;

        let radius = this.size / 2;

        let centerLatLng = {
            lat: this.lat,
            lng: this.lng,
        };
        // @TODO Figure out why I need to * -1 this, not time now
        let topLeft = rotateLatLng(centerLatLng, {
            lat: this.lat - radius,
            lng: this.lng - (radius * aspectRatio),
        }, this.rotation * -1);

        let topRight = rotateLatLng(centerLatLng, {
            lat: this.lat - radius,
            lng: this.lng + (radius * aspectRatio),
        }, this.rotation * -1);

        let bottomRight = rotateLatLng(centerLatLng, {
            lat: this.lat + radius,
            lng: this.lng + (radius * aspectRatio),
        }, this.rotation * -1);

        let bottomLeft = rotateLatLng(centerLatLng, {
            lat: this.lat + radius,
            lng: this.lng - (radius * aspectRatio),
        }, this.rotation * -1);

        return L.polygon([
            // Top left corner
            [topLeft.lat, topLeft.lng],
            // Top right corner
            [topRight.lat, topRight.lng],
            // Bottom right corner
            [bottomRight.lat, bottomRight.lng],
            // Bottom left corner
            [bottomLeft.lat, bottomLeft.lng],
        ], c.map.floorunion.polygonOptions);
    }

    /**
     *
     * @private
     */
    _refreshVisual() {
        console.assert(this instanceof Icon, 'this is not an Icon', this);

        this.layer.setIcon(LeafletIconFloorUnion);
    }

    /**
     * Sets the map icon type ID and refreshes the layer for it.
     * @param mapIconTypeId
     */
    setMapIconTypeId(mapIconTypeId) {
        console.assert(this instanceof FloorUnion, 'this is not a FloorUnion', this);

        // Do nothing - we don't actually have a map icon type
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
