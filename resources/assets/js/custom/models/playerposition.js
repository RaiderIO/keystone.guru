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

/**
 * @param playerPosition {PlayerPosition}
 * @returns {*}
 */
function getPlayerPositionIcon(playerPosition) {
    let template = Handlebars.templates['map_player_position_visual_template'];

    let width = c.map.mapicon.calculateSize(32);
    let height = c.map.mapicon.calculateSize(32);
    // 4 for padding
    let textWidth = width - (4 + 18);
    textWidth += (c.map.leafletSettings.maxNativeZoom - getState().getMapZoomLevel());

    let characterName = playerPosition.character_name ?? '';
    let initials = characterName.length > 0 ? characterName.substring(0, 2).toUpperCase() : '?';

    let handlebarsData = $.extend({}, {
        character_name: characterName,
        initials: initials,
        specialization_icon_url: playerPosition.specialization_icon_url ?? null,
        width: width,
        height: height,
        textWidth: textWidth
    });

    return L.divIcon({
        html: template(handlebarsData),
        iconSize: [width, height],
        iconAnchor: [width / 2, height / 2],
        tooltipAnchor: [0, -(height / 2)],
        popupAnchor: [0, -(height / 2)],
        className: 'player_position'
    });
}

class PlayerPosition extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'playerposition'});

        /** @type {PlayerPositionPlayer|null} */
        this.player = null;

        this.register('object:initialized', this, this._refreshVisual.bind(this));
        getState().register('floor:changed', this, this._onFloorChanged.bind(this));
        getState().register('mapzoomlevel:changed', this, this._refreshVisual.bind(this));
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
                name: 'player_guid',
                type: 'string',
                default: '',
                edit: false,
            }),
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
            new Attribute({
                name: 'class_id',
                type: 'int',
                default: null,
                edit: false,
            }),
            new Attribute({
                name: 'specialization_id',
                type: 'int',
                default: null,
                edit: false,
            }),
            new Attribute({
                name: 'specialization_icon_url',
                type: 'string',
                default: null,
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
            this.layer.setIcon(getPlayerPositionIcon(this));
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

    /**
     * Applies a newly received position, smoothly gliding to it when on the same floor or jumping instantly when the
     * player changed floors.
     *
     * @param lat {Number}
     * @param lng {Number}
     * @param floorId {Number}
     */
    moveTo(lat, lng, floorId) {
        console.assert(this instanceof PlayerPosition, 'this is not a PlayerPosition', this);

        // Don't interpolate across floors - that would glide the marker through invalid space
        if (floorId !== this.floor_id) {
            if (this.player !== null) {
                this.player.stop();
            }

            this.setPosition(lat, lng, floorId);
            return;
        }

        if (this.player === null) {
            this.player = new PlayerPositionPlayer(this);
        }

        this.player.moveTo(lat, lng);
    }

    /**
     * Lightweight per-frame setter used by the player while interpolating. Deliberately does not refresh the visual -
     * rebuilding the icon every frame is far too heavy.
     *
     * @param lat {Number}
     * @param lng {Number}
     */
    setInterpolatedPosition(lat, lng) {
        console.assert(this instanceof PlayerPosition, 'this is not a PlayerPosition', this);

        this.lat = lat;
        this.lng = lng;

        this.layer.setLatLng(L.latLng(lat, lng));
    }

    /**
     * @param classId {Number|null}
     * @param specializationId {Number|null}
     * @param specializationIconUrl {String|null}
     */
    setClassSpecialization(classId, specializationId, specializationIconUrl) {
        console.assert(this instanceof PlayerPosition, 'this is not a PlayerPosition', this);

        this.class_id = classId ?? null;
        this.specialization_id = specializationId ?? null;
        this.specialization_icon_url = specializationIconUrl ?? null;

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

        if (this.player !== null) {
            this.player.stop();
        }

        getState().unregister('floor:changed', this);
        getState().unregister('mapzoomlevel:changed', this);
        this.unregister('object:initialized', this);
    }
}
