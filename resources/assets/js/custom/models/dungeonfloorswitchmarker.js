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

/**
 * @property {Number|null} source_floor_id
 * @property {Number} target_floor_id
 * @property {String} floorCouplingDirection
 * @property {String|null} direction
 */
class DungeonFloorSwitchMarker extends Icon {

    constructor(map, layer) {
        super(map, layer, {name: 'dungeonfloorswitchmarker', hasRouteModelBinding: true});

        let self = this;

        this.label = 'DungeonFloorSwitchMarker';
        // Listen for floor changes
        getState().register('floorid:changed', this, function () {
            // Invalidate the cache
            self._cachedAttributes = null;
            // Rebuild the popup so that we have proper
            self._assignPopup();
        });

        if (getState().isEchoEnabled()) {
            getState().getEcho().register('mouseposition:received', this, this._mousePositionReceived.bind(this));
        }

        // Whenever we have to display which users are on this floor, these users are on here
        this.usersOnThisFloor = [];
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
                name: 'source_floor_id',
                type: 'select',
                values: function () {
                    // Fill it with all floors except our current floor, this is done for floor unions so selecting the current floor would make no sense
                    return getState().getMapContext().getFloorSelectValues(self.floor_id);
                },
                default: null
            }),
            new Attribute({
                name: 'target_floor_id',
                type: 'select',
                values: function () {
                    // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
                    return getState().getMapContext().getFloorSelectValues(self.floor_id);
                },
                default: null
            }),
            new Attribute({
                name: 'floorCouplingDirection',
                type: 'string',
                edit: false,
                save: false
            }),
            new Attribute({
                name: 'direction',
                type: 'select',
                values: function () {
                    return [
                        {id: 'down', name: lang.get('mapicontypes.door_down')},
                        {id: 'left', name: lang.get('mapicontypes.door_left')},
                        {id: 'right', name: lang.get('mapicontypes.door_right')},
                        {id: 'up', name: lang.get('mapicontypes.door_up')},
                    ];
                },
                setter: function (value) {
                    let mapping = {
                        'down': 'door_down',
                        'left': 'door_left',
                        'right': 'door_right',
                        'up': 'door_up',
                    };

                    self.setMapIconType(
                        getState().getMapContext().getMapIconTypeByKey(
                            value === null ? mapping[self.floorCouplingDirection] : mapping[value]
                        )
                    );

                    self.direction = value;
                },
                default: null
            }),
            new Attribute({
                name: 'ingameX',
                type: 'float',
                edit: false,
            }),
            new Attribute({
                name: 'ingameY',
                type: 'float',
                edit: false,
            })
        ]);
    }

    /**
     *
     * @param e
     * @private
     */
    _mousePositionReceived(e) {
        let mousePosition = e.data;

        let changed = false;

        // If the user is on this floor..
        if (mousePosition.floor_id === this.target_floor_id) {
            // Add the user to this floor
            if (!this.usersOnThisFloor.includes(mousePosition.user.public_key)) {
                this.usersOnThisFloor.push(mousePosition.user.public_key);

                changed = true;
            }
        } else {
            // Remove it from the list
            let index = this.usersOnThisFloor.indexOf(mousePosition.user.public_key);
            if (index !== -1) {
                this.usersOnThisFloor.splice(index, 1);

                changed = true;
            }
        }

        if (changed) {
            this.rebindTooltip();
        }
    }

    _getDecorator() {
        let result = null;

        /** @type {DungeonFloorSwitchMarkerMapObjectGroup} */
        let dungeonFloorSwitchMarkerMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);
        // @TODO Hacky fix to disable floor connections in Waycrest Manor - it's bugged there, see #2084
        if (this.floor_id !== 269 && this.source_floor_id !== null && this.target_floor_id !== null) {
            let closestDungeonFloorSwitchMarker = dungeonFloorSwitchMarkerMapObjectGroup.getClosestMarker(
                this.target_floor_id,
                this.source_floor_id,
                this.layer.getLatLng(),
                true
            );

            if (closestDungeonFloorSwitchMarker !== null) {
                result = L.polyline(
                    [this.layer.getLatLng(), closestDungeonFloorSwitchMarker.layer.getLatLng()],
                    c.map.dungeonfloorswitchmarker.floorUnionConnectionPolylineOptions
                );
            }
        }

        return result;
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
            let state = getState();
            // Don't do anything when we have combined floors! We can already see everything
            if (state.getMapFacadeStyle() === MAP_FACADE_STYLE_SPLIT_FLOORS && self.target_floor_id !== null) {
                state.setFloorId(self.target_floor_id);
            }
        });
    }

    /**
     *
     * @returns {{}}
     */
    getTooltipOptions() {
        return {
            permanent: this.usersOnThisFloor.length > 0
        };
    }

    /**
     * Return the text that is displayed on the label of this Map Icon.
     * @returns {string}
     */
    getDisplayText() {
        console.assert(this instanceof DungeonFloorSwitchMarker, 'this is not a DungeonFloorSwitchMarker', this);

        let state = getState();
        if (state.getMapFacadeStyle() === MAP_FACADE_STYLE_FACADE) {
            return '';
        }

        if (this.usersOnThisFloor.length > 0) {
            let echo = state.getEcho();
            let usernames = [];
            for (let i = 0; i < this.usersOnThisFloor.length; i++) {
                let echoUser = echo.getUserByPublicKey(this.usersOnThisFloor[i]);
                if (echoUser !== null) {
                    usernames.push(echoUser.getName());
                }
            }

            return usernames.join(', ');
        }

        let targetFloor = state.getMapContext().getFloorById(this.target_floor_id);

        if (targetFloor !== false) {
            return `${lang.get('messages.dungeonfloorswitchmarker_go_to_label')} ${lang.get(targetFloor.name)}`;
        } else {
            return `${lang.get('messages.dungeonfloorswitchmarker_unknown_label')}`;
        }
    }

    isEditable() {
        return super.isEditable() && getState().getMapContext() instanceof MapContextMappingVersionEdit;
    }

    toString() {
        return `Floor switcher (${this.comment === null ? '' : this.comment.substring(0, 25)})`;
    }

    cleanup() {
        super.cleanup();
        getState().unregister('floorid:changed', this);

        if (getState().isEchoEnabled()) {
            getState().getEcho().unregister('mouseposition:received', this);
        }
    }
}
