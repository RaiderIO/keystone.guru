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

class DungeonFloorSwitchMarker extends MapObject {

    constructor(map, layer) {
        super(map, layer, {name: 'dungeonfloorswitchmarker'});

        this.label = 'DungeonFloorSwitchMarker';
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof DungeonFloorSwitchMarker, 'this was not an DungeonFloorSwitchMarker', this);
        let self = this;

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
        let currentFloorId = getState().getCurrentFloor().id;
        let dungeonData = getState().getMapContext().getDungeon();
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

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            new Attribute({
                name: 'target_floor_id',
                type: 'select',
                values: selectFloors,
                default: -1
            }),
            new Attribute({
                name: 'lat',
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer.getLatLng().lat;
                }
            }),
            new Attribute({
                name: 'lng',
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer.getLatLng().lng;
                }
            })
        ]);
    }

    /**
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        console.warn(remoteMapObject);

        switch (remoteMapObject.direction) {
            case 'up':
                this.layer = new LeafletDungeonFloorSwitchMarkerUp();
                break;
            case 'down':
                this.layer = new LeafletDungeonFloorSwitchMarkerDown();
                break;
            case 'left':
                this.layer = new LeafletDungeonFloorSwitchMarkerLeft();
                break;
            case 'right':
                this.layer = new LeafletDungeonFloorSwitchMarkerRight();
                break;
            default:
                // layer = new LeafletDungeonFloorSwitchMarker();
                break;
        }

        if( this.layer !== null ) {
            this.layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

            let mapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);
            mapObjectGroup.setLayerToMapObject(this.layer, this);
        }
    }

    /**
     * @inheritDoc
     */
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
        if (value && this.layer !== null) {
            let targetFloor = this.map.getFloorById(this.target_floor_id);

            if (targetFloor !== false) {
                this.layer.bindTooltip(`Go to ${targetFloor.name}`, {
                    direction: 'top'
                });
            }
        }
    }
}