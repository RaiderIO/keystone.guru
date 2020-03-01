$(function () {
    L.Draw.DungeonStartMarker = L.Draw.Marker.extend({
        statics: {
            TYPE: 'dungeonstartmarker'
        },
        options: {
            icon: LeafletDungeonStartIcon
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.DungeonStartMarker.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

let LeafletDungeonStartIcon = new L.divIcon({className: 'dungeon_start_icon', iconSize: [32, 32]});

let LeafletDungeonStartMarker = L.Marker.extend({
    options: {
        icon: LeafletDungeonStartIcon
    }
});

class DungeonStartMarker extends MapObject {

    constructor(map, layer) {
        super(map, layer);

        this.label = 'DungeonStartMarker';
    }


    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof DungeonStartMarker, 'this is not a DungeonStartMarker', this);
        super.onLayerInit();

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }
}