class AdminDungeonFloorSwitchMarker extends DungeonFloorSwitchMarker {

    /**
     *
     * @param map
     * @param layer {L.layer}
     */
    constructor(map, layer) {
        super(map, layer);

        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);

        this.target_floor_id = -1;
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, 'this was not an AdminDungeonFloorSwitchMarker', this);
        let self = this;

        // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
        let currentFloorId = getState().getCurrentFloor().id;
        let dungeonData = getState().getDungeonData();
        let selectFloors = [];
        for (let i in dungeonData.floors) {
            if (dungeonData.floors.hasOwnProperty(i)) {
                let floor = dungeonData.floors[i];
                if (floor.id !== currentFloorId) {
                    selectFloors.push({
                        id: floor.id,
                        name: floor.name,
                    });
                }
            }
        }

        return $.extend(super._getAttributes(force), {
            floor_id: new Attribute({
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            target_floor_id: new Attribute({
                type: 'select',
                values: selectFloors,
                default: -1
            }),
            lat: new Attribute({
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer.getLatLng().lat;
                }
            }),
            lng: new Attribute({
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer.getLatLng().lng;
                }
            })
        });
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
        if (this.layer !== 'undefined') {
            let targetFloor = this.map.getFloorById(this.target_floor_id);

            if (targetFloor !== false) {
                this.layer.bindTooltip(targetFloor.name, {
                    direction: 'top'
                });
            }
        }
    }
}