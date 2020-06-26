$(function () {
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
});

class EnemyPack extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'enemypack'});

        this.label = 'Enemy pack';
        this.setColors(c.map.enemypack.colors);

        this.color = null;
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

        return $.extend(super._getAttributes(force), {
            floor_id: new Attribute({
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            label: new Attribute({
                type: 'text',
                default: 'Enemy pack'
            }),
            vertices: new Attribute({
                type: 'array',
                edit: false,
                getter: function(){
                    return self.getVertices();
                }
            })
        });
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
        let coordinates = this.layer.toGeoJSON().geometry.coordinates[0];
        let result = [];
        for (let i = 0; i < coordinates.length - 1; i++) {
            result.push({lat: coordinates[i][1], lng: coordinates[i][0]});
        }
        return result;
    }

    toString() {
        return 'Enemy pack-' + this.id;
    }

    cleanup() {
        super.cleanup();
    }
}