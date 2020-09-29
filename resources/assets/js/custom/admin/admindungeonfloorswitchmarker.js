class AdminDungeonFloorSwitchMarker extends DungeonFloorSwitchMarker {

    /**
     *
     * @param map
     * @param layer {L.layer}
     */
    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        this.target_floor_id = -1;
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, 'this is not a AdminDungeonFloorSwitchMarker', this);
        super.onLayerInit();

        this.layer.off('click');
    }

    /**
     * @inheritDoc
     */
    setSynced(value) {
        super.setSynced(value);
        console.assert(this instanceof DungeonFloorSwitchMarker, 'this is not a DungeonFloorSwitchMarker', this);

        // If we've fully loaded this marker
        if (this.layer !== null) {
            let targetFloor = this.map.getFloorById(this.target_floor_id);

            if (targetFloor !== false) {
                this.layer.bindTooltip(targetFloor.name, {
                    direction: 'top'
                });
            }
        }
    }
}