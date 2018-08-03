$(function () {
    L.Draw.DungeonFloorSwitchMarker = L.Draw.Marker.extend({
        statics: {
            TYPE: 'dungeonfloorswitchmarker'
        },
        options: {
            icon: LeafletDungeonFloorSwitchIcon
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.DungeonFloorSwitchMarker.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

let LeafletDungeonFloorSwitchIcon = L.divIcon({
    html: '<div class="marker_div_icon marker_div_icon_circle_border"><i class="fas fa-door-open"></i></div>',
    iconSize: [30, 30],
    className: 'marker_div_icon_font_awesome marker_div_icon_dungeon_floor_switch_marker'
});

let LeafletDungeonFloorSwitchMarker = L.Marker.extend({
    options: {
        icon: LeafletDungeonFloorSwitchIcon
    }
});

class DungeonFloorSwitchMarker extends MapObject {

    constructor(map, layer) {
        super(map, layer);

        this.label = 'DungeonFloorSwitchMarker';

        this.setSynced(true);
    }


    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof DungeonFloorSwitchMarker, this, 'this is not a DungeonFloorSwitchMarker');
        super.onLayerInit();

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }
}