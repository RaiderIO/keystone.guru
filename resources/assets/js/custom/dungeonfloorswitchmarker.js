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

let LeafletDungeonFloorSwitchIcon = new L.divIcon({className: 'door_icon', iconSize: [32, 32]});

let LeafletDungeonFloorSwitchMarker = L.Marker.extend({
    options: {
        icon: LeafletDungeonFloorSwitchIcon
    }
});

class DungeonFloorSwitchMarker extends MapObject {

    constructor(map, layer) {
        super(map, layer);

        this.label = 'DungeonFloorSwitchMarker';
    }


    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof DungeonFloorSwitchMarker, this, 'this is not a DungeonFloorSwitchMarker');
        super.onLayerInit();

        let self = this;

        this.layer.on('click', function () {
            // Reference to the sidebar floor is stored in the sidebar. Bit of a hack but eh.
            let sidebar = _inlineManager.getInlineCode('common/maps/sidebar');
            $(sidebar.options.switchDungeonFloorSelect).val(self.target_floor_id).trigger('change').change();
            refreshSelectPickers();
        });

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }

    setSynced(value) {
        super.setSynced(value);

        // If we've fully loaded this marker
        if (value && typeof this.layer !== 'undefined') {
            let targetFloor = this.map.getFloorById(this.target_floor_id);

            if (targetFloor !== false) {
                this.layer.bindTooltip("Go to " + targetFloor.name);
            }
        }
    }
}