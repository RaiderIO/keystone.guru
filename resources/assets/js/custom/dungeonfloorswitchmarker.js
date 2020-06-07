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

let defaultDungeonFloorSwitchIconSettings = {iconSize: [32, 32], tooltipAnchor: [0, -16], popupAnchor: [0, -16]};
let LeafletDungeonFloorSwitchIcon = new L.divIcon($.extend({className: 'door_icon'}, defaultDungeonFloorSwitchIconSettings));
let LeafletDungeonFloorSwitchIconUp = new L.divIcon($.extend({className: 'door_up_icon'}, defaultDungeonFloorSwitchIconSettings));
let LeafletDungeonFloorSwitchIconDown = new L.divIcon($.extend({className: 'door_down_icon'}, defaultDungeonFloorSwitchIconSettings));
let LeafletDungeonFloorSwitchIconLeft = new L.divIcon($.extend({className: 'door_left_icon'}, defaultDungeonFloorSwitchIconSettings));
let LeafletDungeonFloorSwitchIconRight = new L.divIcon($.extend({className: 'door_right_icon'}, defaultDungeonFloorSwitchIconSettings));

let LeafletDungeonFloorSwitchMarker = L.Marker.extend({
    options: {
        icon: LeafletDungeonFloorSwitchIcon
    }
});
let LeafletDungeonFloorSwitchMarkerUp = L.Marker.extend({
    options: {
        icon: LeafletDungeonFloorSwitchIconUp
    }
});
let LeafletDungeonFloorSwitchMarkerDown = L.Marker.extend({
    options: {
        icon: LeafletDungeonFloorSwitchIconDown
    }
});
let LeafletDungeonFloorSwitchMarkerLeft = L.Marker.extend({
    options: {
        icon: LeafletDungeonFloorSwitchIconLeft
    }
});
let LeafletDungeonFloorSwitchMarkerRight = L.Marker.extend({
    options: {
        icon: LeafletDungeonFloorSwitchIconRight
    }
});

class DungeonFloorSwitchMarker extends MapObject {

    constructor(map, layer) {
        super(map, layer);

        this.label = 'DungeonFloorSwitchMarker';
    }


    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof DungeonFloorSwitchMarker, 'this is not a DungeonFloorSwitchMarker', this);
        super.onLayerInit();

        let self = this;

        this.layer.on('click', function () {
            // Tol'dagor doors don't have a target (locked doors)
            if (self.target_floor_id > 0) {
                getState().setFloorId(self.target_floor_id);
            }
        });

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }

    setSynced(value) {
        super.setSynced(value);
        console.assert(this instanceof DungeonFloorSwitchMarker, 'this is not a DungeonFloorSwitchMarker', this);

        // If we've fully loaded this marker
        if (value && typeof this.layer !== 'undefined') {
            let targetFloor = this.map.getFloorById(this.target_floor_id);

            if (targetFloor !== false) {
                this.layer.bindTooltip('Go to ' + targetFloor.name, {
                    direction: 'top'
                });
            }
        }
    }
}