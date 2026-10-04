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
 * @property {Number|null} linked_dungeon_floor_switch_marker_id
 * @property {String} floorCouplingDirection
 * @property {String|null} direction
 * @property {Boolean} hidden_in_facade
 */
class DungeonFloorSwitchMarker extends Icon {

    constructor(map, layer) {
        super(map, layer, {name: 'dungeonfloorswitchmarker', has_route_model_binding: true});

        let self = this;

        this.mouseOver = false;

        this.label = 'DungeonFloorSwitchMarker';
        // Listen for floor changes
        getState().register('floorid:changed', this, function () {
            // Invalidate the cache
            self._cachedAttributes = null;
            // Rebuild the popup so that we have proper
            self._assignPopup();
        });

        // The tooltip may be bound before target_floor_id is loaded (attributes are assigned in
        // order and map_icon_type_id's setter eagerly refreshes the visual/tooltip), which bakes
        // in the 'unknown floor' fallback text. Rebind once all attributes have loaded.
        this.register('object:initialized', this, function () {
            self.bindTooltip();

            // The two-way icon depends on the linked marker's hidden_in_facade, and either marker of a pair
            // may finish loading last
            self._refreshMapIconType();
            let linkedDungeonFloorSwitchMarker = self._getLinkedDungeonFloorSwitchMarker();
            if (linkedDungeonFloorSwitchMarker !== null) {
                linkedDungeonFloorSwitchMarker._refreshMapIconType();
            }
        });

        // Icon only redraws a visible marker, and the two-way icon is decided before the markers are shown
        this._refreshVisualOnShown = this._refreshVisual.bind(this);
        this.register('shown', this, this._refreshVisualOnShown);

        if (getState().isEchoEnabled()) {
            getState().getEchoHandler().register('mouseposition:received', this, this._mousePositionReceived.bind(this));
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
                name: 'linked_dungeon_floor_switch_marker_id',
                type: 'int',
                default: -1
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
                    self.direction = value;

                    self._refreshMapIconType();
                },
                default: null
            }),
            new Attribute({
                name: 'hidden_in_facade',
                type: 'bool',
                default: 0,
            }),
            new Attribute({
                name: 'ingameX',
                type: 'float',
                edit: false,
                save: false
            }),
            new Attribute({
                name: 'ingameY',
                type: 'float',
                edit: false,
                save: false
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

    /**
     * @returns {DungeonFloorSwitchMarker|null}
     * @private
     */
    _getLinkedDungeonFloorSwitchMarker() {
        if (this.linked_dungeon_floor_switch_marker_id === null) {
            return null;
        }

        /** @type {DungeonFloorSwitchMarkerMapObjectGroup} */
        let dungeonFloorSwitchMarkerMapObjectGroup = this.map.mapObjectGroupManager.getDungeonFloorSwitchMarkerMapObjectGroup();

        return dungeonFloorSwitchMarkerMapObjectGroup.findMapObjectById(this.linked_dungeon_floor_switch_marker_id);
    }

    /**
     * The mapping editor always shows every marker on split floors, whatever the facade style is.
     * @returns {boolean}
     * @private
     */
    _isOnFacade() {
        let state = getState();

        return !(state.getMapContext() instanceof MapContextMappingVersionEdit) && state.isCurrentDungeonFacadeEnabled();
    }

    /**
     * On the facade, a marker whose linked marker is hidden there is the only marker left for that transition,
     * so it points both ways.
     * @returns {boolean}
     * @private
     */
    _isOnlyMarkerOfTransitionOnFacade() {
        if (!this._isOnFacade()) {
            return false;
        }

        let linkedDungeonFloorSwitchMarker = this._getLinkedDungeonFloorSwitchMarker();

        return linkedDungeonFloorSwitchMarker !== null && linkedDungeonFloorSwitchMarker.hidden_in_facade;
    }

    /**
     * @returns {String|undefined}
     * @private
     */
    _getMapIconTypeKey() {
        let direction = this.direction ?? this.floorCouplingDirection;

        if (this._isOnlyMarkerOfTransitionOnFacade()) {
            if (direction === 'left' || direction === 'right') {
                return 'door_left_right';
            } else if (direction === 'up' || direction === 'down') {
                return 'door_up_down';
            }
        }

        return {
            'down': 'door_down',
            'left': 'door_left',
            'right': 'door_right',
            'up': 'door_up',
        }[direction];
    }

    /**
     * @private
     */
    _refreshMapIconType() {
        this.setMapIconType(getState().getMapContext().getMapIconTypeByKey(this._getMapIconTypeKey()));
    }

    _getDecorator() {
        let result = null;

        if (getState().isCurrentDungeonFacadeEnabled()) {
            let linkedDungeonFloorSwitchMarker = this._getLinkedDungeonFloorSwitchMarker();

            if (linkedDungeonFloorSwitchMarker !== null && linkedDungeonFloorSwitchMarker.isVisible()) {
                let options = c.map.dungeonfloorswitchmarker.floorUnionConnectionPolylineOptions;

                if (this.mouseOver) {
                    options = $.extend({}, options, c.map.dungeonfloorswitchmarker.floorUnionConnectionPolylineMouseoverOptions);
                }

                result = L.polyline(
                    [this.layer.getLatLng(), linkedDungeonFloorSwitchMarker.layer.getLatLng()],
                    options
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
            if (!state.isCurrentDungeonFacadeEnabled() && self.target_floor_id !== null) {
                state.setFloorId(self.target_floor_id);
            }
        }).on('mouseover', function () {
            self.mouseOver = true;
            self._rebuildDecorator();
        }).on('mouseout', function () {
            self.mouseOver = false;
            self._rebuildDecorator();
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

        // We do not know the names of the other floors at this point - awkward
        if (state.isCurrentDungeonFacadeEnabled()) {
            return '';
        }

        if (this.usersOnThisFloor.length > 0) {
            let echo = state.getEchoHandler();
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
            // if (state.isCurrentDungeonFacadeEnabled()) {
            //     return lang.get('js.dungeonfloorswitchmarker_to_label', {floor: lang.get(targetFloor.name)});
            // } else {
            return lang.get('js.dungeonfloorswitchmarker_go_to_label', {floor: lang.get(targetFloor.name)});
            // }
        } else {
            return `${lang.get('js.dungeonfloorswitchmarker_unknown_label')}`;
        }
    }

    isEditable() {
        return super.isEditable() && getState().getMapContext() instanceof MapContextMappingVersionEdit;
    }

    toString() {
        return `Floor switcher (${this.comment === null ? '' : this.comment.substring(0, 25)})`;
    }

    shouldBeVisible() {
        if (this._isOnFacade() && this.hidden_in_facade) {
            return false;
        }

        return super.shouldBeVisible();
    }

    cleanup() {
        super.cleanup();
        getState().unregister('floorid:changed', this);
        this.unregister('object:initialized', this);
        this.unregister('shown', this, this._refreshVisualOnShown);

        if (getState().isEchoEnabled()) {
            getState().getEchoHandler().unregister('mouseposition:received', this);
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DungeonFloorSwitchMarker,
    };
}
