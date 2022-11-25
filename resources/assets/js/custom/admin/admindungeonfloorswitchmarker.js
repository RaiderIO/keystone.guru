class AdminDungeonFloorSwitchMarker extends DungeonFloorSwitchMarker {

    /**
     *
     * @param map
     * @param layer {L.layer}
     */
    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        this.target_floor_id = null;
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, 'this is not an AdminDungeonFloorSwitchMarker', this);
        super.onLayerInit();

        this.layer.off('click');
    }

    /**
     * Return the text that is displayed on the label of this Map Icon.
     * @returns {string}
     */
    getDisplayText() {
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, 'this is not an AdminDungeonFloorSwitchMarker', this);

        let targetFloor = getState().getMapContext().getFloorById(this.target_floor_id);

        if (targetFloor !== false) {
            return `Target: ${lang.get(targetFloor.name)}`;
        } else {
            return `Unknown target`;
        }
    }
}
