// $(function () {
L.Draw.FloorUnionArea = L.Draw.Polygon.extend({
    statics: {
        TYPE: 'floorunionarea'
    },
    options: {},
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.FloorUnionArea.TYPE;

        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

// });

class FloorUnionArea extends VersionableMapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'floorunionarea', hasRouteModelBinding: true});

        this.color = null;
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof FloorUnionArea, 'this was not an FloorUnionArea', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).filter((attribute) => {
            return !['faction', 'teeming'].includes(attribute.options.name);
        }).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            new Attribute({
                name: 'floor_union_id',
                type: 'int',
                edit: true
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

    isEditableByPopup() {
        return getState().isMapAdmin();
    }

    /**
     *
     * @returns {[]}
     */
    getVertices() {
        console.assert(this instanceof FloorUnionArea, 'this is not an FloorUnionArea', this);

        let coordinates = this.layer.toGeoJSON().geometry.coordinates[0];
        let result = [];
        for (let i = 0; i < coordinates.length - 1; i++) {
            result.push({lat: coordinates[i][1], lng: coordinates[i][0]});
        }
        return result;
    }

    toString() {
        console.assert(this instanceof FloorUnionArea, 'this is not an FloorUnionArea', this);

        return 'Floor Union Area-' + this.id;
    }

    cleanup() {
        console.assert(this instanceof FloorUnionArea, 'this is not an FloorUnionArea', this);

        super.cleanup();
    }
}
