let LeafletIconUserMousePositionUnknown = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown user_mouse_position_unknown'
});

let LeafletIconUserMousePositionMarker = L.Marker.extend({
    options: {
        icon: LeafletIconUserMousePositionUnknown
    }
});

/**
 * @param userMousePosition {UserMousePosition}
 */
function getUserMousePositionIcon(userMousePosition) {
    let template = Handlebars.templates['map_user_mouse_position_visual_template'];

    let width = c.map.mapicon.calculateSize(32);
    let height = c.map.mapicon.calculateSize(32);
    // Remove 6 for border, then 17 for 2 characters wide
    let textWidth = width / 2;
    textWidth -= (c.map.settings.maxZoom - getState().getMapZoomLevel());

    let handlebarsData = $.extend({}, {
        user_id: userMousePosition.id,
        initials: userMousePosition.initials,
        color: userMousePosition.color,
        avatar_url: userMousePosition.avatar_url,
        width: width,
        height: height,
        textWidth: textWidth
    });

    return L.divIcon({
        html: template(handlebarsData),
        iconSize: [width, height],
        tooltipAnchor: [0, -(height / 2)],
        popupAnchor: [0, -(height / 2)],
        className: 'user_mouse_position'
    });
}


L.Draw.UserMousePosition = L.Draw.Marker.extend({
    statics: {
        TYPE: MAP_OBJECT_GROUP_USER_MOUSE_POSITION
    },
    options: {
        icon: LeafletIconUserMousePositionUnknown
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.UserMousePosition.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

class UserMousePosition extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'usermouseposition'});

        this.player = null;

        this.register('object:changed', this, this._refreshVisual.bind(this));
        getState().register('mapzoomlevel:changed', this, this._refreshVisual.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof UserMousePosition, 'this is not a UserMousePosition', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int', // Not changeable by user
                edit: false, // Not directly changeable by user
            }),
            new Attribute({
                name: 'initials',
                type: 'string',
                default: '',
                edit: false,
            }),
            new Attribute({
                name: 'color',
                type: 'string',
                default: '',
                edit: false,
            }),
            new Attribute({
                name: 'avatar_url',
                type: 'string',
                default: null,
                edit: false,
            }),
        ]);
    }

    /**
     *
     * @private
     */
    _refreshVisual() {
        console.assert(this instanceof UserMousePosition, 'this is not a UserMousePosition', this);

        // Init once or only when visible (as in, only update icons on the same floor)
        if (this.isVisible() || (this.layer !== null && this.layer.getIcon() === LeafletIconUserMousePositionUnknown)) {
            this.layer.setIcon(getUserMousePositionIcon(this));
        }
        // // @TODO Refresh the layer; required as a workaround since in mapiconmapobjectgroup we don't know the map_icon_type upon init,
        // // thus we don't know if this will be editable or not. In the sync this will get called and the edit state is known
        // // after which this function will function properly
        // this.onLayerInit();
    }

    /**
     *
     * @param lat {float}
     * @param lng {float}
     */
    setPosition(lat, lng) {
        console.assert(this instanceof UserMousePosition, 'this was not a UserMousePosition', this);
        this.lat = lat;
        this.lng = lng;

        this.layer.setLatLng(L.latLng(lat, lng));
    }

    /**
     * @param message {MousePositionMessage}
     */
    onPositionsReceived(message) {
        console.assert(this instanceof UserMousePosition, 'this was not a UserMousePosition', this);

        // Only perform this when we're on the same floor
        if (message.floor_id === getState().getCurrentFloor().id) {
            // Abort any existing player - if there was any
            if( this.player !== null ) {
                this.player.stop();
            }

            this.player = new UserMousePositionPlayer(this, message, this.player);
            this.player.start();
        }

        this.floor_id = message.floor_id;

        // Hide/show ourselves based on the received location
        let userMousePositionMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_USER_MOUSE_POSITION);
        userMousePositionMapObjectGroup.setMapObjectVisibility(this, this.shouldBeVisible());
    }

    isEditable() {
        return false;
    }

    isDeletable() {
        return false;
    }

    isEditableByPopup() {
        return false;
    }

    save() {
        // This map object is not savable - it does not represent anything that is in the database
    }

    delete() {

    }

    cleanup() {
        super.cleanup();

        getState().unregister('mapzoomlevel:changed', this);
        this.unregister('object:changed', this);
    }
}