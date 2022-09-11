// $(function () {
L.Draw.MountableArea = L.Draw.Polygon.extend({
    statics: {
        TYPE: 'mountablearea'
    },
    options: {},
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.MountableArea.TYPE;

        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

// });

class AdminMountableArea extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'mountablearea'});

        this.color = null;
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof AdminMountableArea, 'this was not an AdminMountableArea', this);

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
        return true;
    }

    /**
     *
     * @returns {[]}
     */
    getVertices() {
        console.assert(this instanceof AdminMountableArea, 'this is not an AdminMountableArea', this);

        let coordinates = this.layer.toGeoJSON().geometry.coordinates[0];
        let result = [];
        for (let i = 0; i < coordinates.length - 1; i++) {
            result.push({lat: coordinates[i][1], lng: coordinates[i][0]});
        }
        return result;
    }

    toString() {
        console.assert(this instanceof AdminMountableArea, 'this is not an AdminMountableArea', this);

        return 'Mountable area-' + this.id;
    }

    cleanup() {
        console.assert(this instanceof AdminMountableArea, 'this is not an AdminMountableArea', this);

        super.cleanup();
    }
}
