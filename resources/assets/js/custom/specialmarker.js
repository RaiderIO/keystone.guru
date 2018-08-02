const SPECIAL_MARKER_DUNGEON_START = 'dungeon_start';
const SPECIAL_MARKER_FLOOR_SWITCH = 'dungeon_floor_switch';

class SpecialMarker extends MapObject {

    constructor(map, layer, type) {
        super(map, layer);

        this.label = 'SpecialMarker';

        this.setSynced(true);
    }


    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof SpecialMarker, this, 'this is not a SpecialMarker');
        super.onLayerInit();
        this.layer.setStyle({fillOpacity: 0.6});

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }
}