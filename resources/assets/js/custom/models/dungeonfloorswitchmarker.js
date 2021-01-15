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

class DungeonFloorSwitchMarker extends Icon {

    constructor(map, layer) {
        super(map, layer, {name: 'dungeonfloorswitchmarker'});

        let self = this;

        this.label = 'DungeonFloorSwitchMarker';
        // Listen for floor changes
        getState().register('floorid:changed', this, function () {
            // Invalidate the cache
            self._cachedAttributes = null;
            // Rebuild the popup so that we have proper
            self._assignPopup();
        });
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

        // Bit of an hack to hide properties that should not be editable by the user - we set them manually based on other fields
        let superAttributes = super._getAttributes(force);
        for (let i = 0; i < superAttributes.length; i++) {
            let attribute = superAttributes[i];
            if (attribute.options.name === 'comment') {
                attribute.options.edit = false;
            } else if (attribute.options.name === 'map_icon_type_id') {
                attribute.options.edit = false;
            }
        }

        return this._cachedAttributes = superAttributes.concat([
            new Attribute({
                name: 'target_floor_id',
                type: 'select',
                values: function () {
                    // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
                    let currentFloorId = self.floor_id;
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

                    return selectFloors;
                },
                default: -1
            }),
            new Attribute({
                name: 'direction',
                type: 'select',
                edit: false, // Not directly changeable by user, should be done in the dungeon edit page
                values: function () {
                    return [
                        {id: 'down', name: 'Down'},
                        {id: 'left', name: 'Left'},
                        {id: 'right', name: 'Right'},
                        {id: 'up', name: 'Up'},
                    ];
                },
                setter: function (value) {
                    let mapping = {
                        'down': 'Door Down',
                        'left': 'Door Left',
                        'right': 'Door Right',
                        'up': 'Door Up',
                    };

                    // console.log(value, mapping[value], getState().getMapContext().getMapIconTypeByName(mapping[value]));

                    self.setMapIconType(
                        getState().getMapContext().getMapIconTypeByName(mapping[value])
                    );

                    self.direction = value;
                },
                default: 'down'
            }),
        ]);
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
    }

    /**
     * Return the text that is displayed on the label of this Map Icon.
     * @returns {string}
     */
    getDisplayText() {
        console.assert(this instanceof DungeonFloorSwitchMarker, 'this is not a DungeonFloorSwitchMarker', this);

        let targetFloor = this.map.getFloorById(this.target_floor_id);

        if (targetFloor !== false) {
            return `Go to ${targetFloor.name}`;
        } else {
            return `Unknown`;
        }
    }

    toString() {
        return `Floor switcher (${this.comment.substring(0, 25)})`;
    }

    cleanup() {
        super.cleanup();

        getState().unregister('floorid:changed', this);
    }
}