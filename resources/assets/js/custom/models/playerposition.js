let LeafletIconPlayerPositionUnknown = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown user_mouse_position_unknown'
});

let LeafletIconPlayerPositionMarker = L.Marker.extend({
    options: {
        icon: LeafletIconPlayerPositionUnknown
    }
});

class PlayerPosition extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'playerposition'});

        this.register('object:initialized', this, this._refreshVisual.bind(this));
        getState().register('floor:changed', this, this._onFloorChanged.bind(this));
    }

    /**
     * @private
     */
    _onFloorChanged() {
        let group = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PLAYER_POSITION);
        group.setMapObjectVisibility(this, this.shouldBeVisible());
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof PlayerPosition, 'this is not a PlayerPosition', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'character_name',
                type: 'string',
                default: '',
                edit: false,
            }),
            new Attribute({
                name: 'lat',
                type: 'double',
                default: 0,
                edit: false,
            }),
            new Attribute({
                name: 'lng',
                type: 'double',
                default: 0,
                edit: false,
            }),
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false,
            }),
        ]);
    }

    /**
     * @private
     */
    _refreshVisual() {
        console.assert(this instanceof PlayerPosition, 'this is not a PlayerPosition', this);

        if (this.layer !== null) {
            console.log('refreshing visual');

            this.layer.setIcon(L.divIcon({
                html: `<div class="live-session-player-marker" style="width: 80px; height: 24px;"><span>${this.character_name}</span></div>`,
                className: 'live-session-player-marker-container',
                iconSize: [80, 24],
                iconAnchor: [40, 12],
            }));
        }
    }

    /**
     * @param lat {Number}
     * @param lng {Number}
     * @param floorId {Number}
     */
    setPosition(lat, lng, floorId) {
        console.assert(this instanceof PlayerPosition, 'this is not a PlayerPosition', this);

        this.lat = lat;
        this.lng = lng;
        this.floor_id = floorId;

        this.layer.setLatLng(L.latLng(lat, lng));
        this._refreshVisual();
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
        // Not persisted
    }

    delete() {
        // Not persisted
    }

    cleanup() {
        super.cleanup();

        getState().unregister('floor:changed', this);
        this.unregister('object:initialized', this);
    }
}
