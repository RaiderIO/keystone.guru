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

        this.floorLayer = null;
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

    /**
     *
     * @private
     */
    _refreshVisual() {
        console.assert(this instanceof Icon, 'this is not an Icon', this);

        this.layer.setIcon(LeafletIconFloorUnion);

        let floorUnionMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_FLOOR_UNION);
        if( this.floorLayer !== null ) {
            floorUnionMapObjectGroup.layerGroup.removeLayer(this.floorLayer);
        }

        let aspectRatio = 1.5;
        this.floorLayer = L.polygon([
            // Top left corner
            [
                this.lat - this.size, this.lng - (this.size * aspectRatio)
            ],
            // Top right corner
            [
                this.lat - this.size, this.lng + (this.size * aspectRatio)
            ],
            // Bottom right corner
            [
                this.lat + this.size, this.lng + (this.size * aspectRatio)
            ],
            // Bottom left corner
            [
                this.lat + this.size, this.lng - (this.size * aspectRatio)
            ],
        ], c.map.floorunion.polygonOptions);
        floorUnionMapObjectGroup.layerGroup.addLayer(this.floorLayer);
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
