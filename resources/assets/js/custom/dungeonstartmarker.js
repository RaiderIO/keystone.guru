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

let LeafletDungeonStartIcon = L.divIcon({
    html: '<div class="marker_div_icon marker_div_icon_circle_border"><i class="fas fa-flag"></i></div>',
    iconSize: [30, 30],
    className: 'marker_div_icon_font_awesome marker_div_icon_dungeon_start_marker'
});

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