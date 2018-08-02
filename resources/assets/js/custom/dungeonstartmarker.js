$(function () {
    L.Draw.DungeonStartMarker = L.Draw.CircleMarker.extend({
        statics: {
            TYPE: 'dungeonstartmarker'
        },
        options: {},
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.DungeonStartMarker.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});


class DungeonStartMarker extends MapObject {

    constructor(map, layer) {
        super(map, layer);

        this.label = 'DungeonStartMarker';

        this.setSynced(true);
    }


    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof DungeonStartMarker, this, 'this is not a DungeonStartMarker');
        super.onLayerInit();
        this.layer.setStyle({fillOpacity: 0.6});

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }
}