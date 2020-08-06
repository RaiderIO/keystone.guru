$(function () {
    L.Draw.MapIcon = L.Draw.Marker.extend({
        statics: {
            TYPE: 'mapicon'
        },
        options: {
            icon: LeafletMapIconUnknown
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.MapIcon.TYPE;
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });

    // L.Draw.ObeliskGatewayMapIcon is defined in init function down below!
});

/**
 * Get the Leaflet Marker that represents said mapIconType
 * @param mapIconType null|obj When null, default unknown marker type is returned
 * @param editModeEnabled bool
 * @param deleteModeEnabled bool
 * @returns {*}
 */
function getMapIconLeafletIcon(mapIconType, editModeEnabled, deleteModeEnabled) {
    let icon;
    if (mapIconType === null) {
        console.warn('Unable to find mapIconType for null');
        icon = (editModeEnabled ? LeafletMapIconUnknownEditMode : (deleteModeEnabled ? LeafletMapIconUnknownDeleteMode : LeafletMapIconUnknown));
    } else {
        let template = Handlebars.templates['map_map_icon_visual_template'];

        let handlebarsData = $.extend({}, mapIconType, {
            selectedclass: (editModeEnabled ? ' leaflet-edit-marker-selected' : (deleteModeEnabled ? ' leaflet-edit-marker-selected delete' : '')),
            width: mapIconType.width,
            height: mapIconType.height
        });

        icon = L.divIcon({
            html: template(handlebarsData),
            iconSize: [mapIconType.width, mapIconType.height],
            tooltipAnchor: [0, -(mapIconType.height / 2)],
            popupAnchor: [0, -(mapIconType.height / 2)],
            className: 'map_icon_' + mapIconType.key
        });
    }
    return icon;
}

let LeafletMapIconUnknown = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown'
});

let LeafletMapIconUnknownEditMode = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown leaflet-edit-marker-selected'
});

let LeafletMapIconUnknownDeleteMode = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown leaflet-edit-marker-selected delete'
});

let LeafletMapIconMarker = L.Marker.extend({
    options: {
        icon: LeafletMapIconUnknown
    }
});

/**
 * @property floor_id int
 * @property map_icon_type_id int
 * @property linked_awakened_obelisk_id int
 * @property permanent_tooltip int
 * @property seasonal_index int
 * @property comment string
 */
class MapIcon extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'map_icon', route_suffix: 'mapicon'});

        let self = this;

        this.map_icon_type = getState().getUnknownMapIconType();
        this.label = 'MapIcon';

        this.setSynced(false);
        this.register('synced', this, this._synced.bind(this));
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            if (mapStateChangedEvent.data.previousMapState instanceof EditMapState ||
                mapStateChangedEvent.data.newMapState instanceof EditMapState ||
                mapStateChangedEvent.data.previousMapState instanceof DeleteMapState ||
                mapStateChangedEvent.data.newMapState instanceof DeleteMapState) {
                self._refreshVisual();
            }
        });
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;
        let mapIconTypes = getState().getMapIconTypes();
        let unknownMapIcon = getState().getUnknownMapIconType();

        let editableMapIconTypes = [];
        for (let i in mapIconTypes) {
            // Only editable types!
            if (mapIconTypes.hasOwnProperty(i)) {
                let mapIconType = mapIconTypes[i];
                if (mapIconType.isEditable() &&
                    // Skip unknown map icons, that should be a one time state when placing the icon, not a selectable state
                    mapIconType.id !== unknownMapIcon.id) {
                    // Generate html if necessary
                    if (typeof mapIconType.html === 'undefined') {
                        let template = Handlebars.templates['map_map_icon_select_option_template'];

                        // Direct assign to the object that is in the array so we're sure this change sticks for others
                        mapIconTypes[i].html = template(mapIconType);
                    }

                    editableMapIconTypes.push(mapIconType);
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
                name: 'map_icon_type_id',
                type: 'select',
                values: editableMapIconTypes,
                default: -1,
                setter: this.setMapIconTypeId.bind(this)
            }),
            new Attribute({
                // Reads team_id, stores as show_across_team
                name: 'team_id',
                type: 'bool',
                default: false,
                edit: getState().getDungeonRoute().teamId >= 1,
                setter: function (value) {
                    // If team_id is not null, we show this across the entire team
                    this.show_across_team = value;
                },
                getter: function () {
                    return this.show_across_team ? getState().getDungeonRoute().teamId : null;
                }
            }),
            new Attribute({
                name: 'linked_awakened_obelisk_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: null
            }),
            new Attribute({
                name: 'permanent_tooltip',
                type: 'bool',
                default: false
            }),
            new Attribute({
                name: 'seasonal_index',
                type: 'int',
                admin: true,
                default: null
            }),
            new Attribute({
                name: 'comment',
                type: 'textarea',
                default: ''
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

    _synced() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        // Recreate the tooltip
        this.bindTooltip();
        this._refreshVisual();
    }

    _refreshVisual() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        this.layer.setIcon(
            getMapIconLeafletIcon(this.map_icon_type,
                this.map.getMapState() instanceof EditMapState && this.isEditable(),
                this.map.getMapState() instanceof DeleteMapState && this.isDeletable()
            )
        );
        // // @TODO Refresh the layer; required as a workaround since in mapiconmapobjectgroup we don't know the map_icon_type upon init,
        // // thus we don't know if this will be editable or not. In the sync this will get called and the edit state is known
        // // after which this function will function properly
        // this.onLayerInit();
    }

    /**
     * Sets the map icon type ID and refreshes the layer for it.
     * @param mapIconTypeId
     */
    setMapIconTypeId(mapIconTypeId) {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        this.map_icon_type_id = mapIconTypeId;

        // Set the icon and refresh the visual
        this.map_icon_type = getState().getMapIconType(this.map_icon_type_id);
        this._refreshVisual();
    }

    /**
     * @returns {MapIconType}
     */
    getMapIconType() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        console.assert(this.map_icon_type instanceof MapIconType, 'mapIconType is not a MapIconType', this.map_icon_type);
        return this.map_icon_type;
    }

    /**
     * Return the text that is displayed on the label of this Map Icon.
     * @returns {string}
     */
    getDisplayText() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        return this.comment.length > 0 ? this.comment : this.map_icon_type.name;
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        // Admin may edit everything, but not useful when editing a dungeonroute
        return this.map_icon_type.isEditable() && this.linked_awakened_obelisk_id === null;
    }

    /**
     * @inheritDoc
     */
    isDeletable() {
        return this.map_icon_type.isDeletable() && this.linked_awakened_obelisk_id === null;
    }

    /**
     * @inheritDoc
     */
    bindTooltip() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        this.unbindTooltip();

        if (this.comment.length > 0 || (this.map_icon_type !== null && this.map_icon_type.name.length > 0)) {
            let text = this.getDisplayText();

            // Wrap the text
            if (text.length > 75) {
                this.layer.bindTooltip(
                    jQuery('<div/>', {
                        class: 'map_map_icon_comment_tooltip'
                    }).text(text)[0].outerHTML, {
                        direction: 'top',
                        permanent: this.permanent_tooltip
                    }
                );
            } else {
                this.layer.bindTooltip(text, {
                    direction: 'top',
                    permanent: this.permanent_tooltip
                });
            }
        }
    }

    toString() {
        return `Map icon (${this.comment.substring(0, 25)})`;
    }

    cleanup() {
        super.cleanup();

        this.map.unregister('map:mapstatechanged', this);
        this.unregister('synced', this);
    }
}