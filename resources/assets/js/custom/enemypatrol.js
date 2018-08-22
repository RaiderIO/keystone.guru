$(function () {
    L.Draw.EnemyPatrol = L.Draw.Polyline.extend({
        options: {
            shapeOptions: {
                color: 'red',
                weight: 4
            },
            zIndexOffset: 1000,
        },
        statics: {
            TYPE: 'enemypatrol'
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.EnemyPatrol.TYPE;
            console.log('this:', this);
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

class EnemyPatrol extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'EnemyPatrol';
        // console.log(rand);
        // let hex = "#" + color.values[0].toString(16) + color.values[1].toString(16) + color.values[2].toString(16);

        this.setSynced(true);
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof EnemyPatrol, this, 'this is not an EnemyPatrol');
        super.onLayerInit();

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }
}