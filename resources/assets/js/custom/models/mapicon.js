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
 * @returns {*}
 */
function getMapIconLeafletIcon(mapIconType, editModeEnabled) {
    let icon;
    if (mapIconType === null) {
        console.warn('Unable to find mapIconType for null');
        icon = editModeEnabled ? LeafletMapIconUnknownEditMode : LeafletMapIconUnknown;
    } else {
        let template = Handlebars.templates['map_map_icon_visual_template'];

        let handlebarsData = $.extend({}, mapIconType, {
            selectedclass: (editModeEnabled ? ' leaflet-edit-marker-selected' : ''),
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
        super(map, layer);

        let self = this;

        this.map_icon_type = getState().getUnknownMapIconType();
        this.linked_awakened_obelisk_polyline = null;
        this.label = 'MapIcon';

        this.setSynced(false);
        this.register('synced', this, this._synced.bind(this));
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            if (mapStateChangedEvent.data.previousMapState instanceof EditMapState ||
                mapStateChangedEvent.data.newMapState instanceof EditMapState) {
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

        return $.extend(super._getAttributes(force), {
            floor_id: new Attribute({
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            map_icon_type_id: new Attribute({
                type: 'select',
                values: editableMapIconTypes,
                default: -1,
                setter: this.setMapIconTypeId.bind(this)
            }),
            linked_awakened_obelisk_id: new Attribute({
                type: 'int',
                edit: false, // Not directly changeable by user
                default: null,
                setter: this.setLinkedAwakenedObeliskId.bind(this)
            }),
            permanent_tooltip: new Attribute({
                type: 'bool',
                default: false
            }),
            seasonal_index: new Attribute({
                type: 'int',
                admin: true,
                default: null
            }),
            comment: new Attribute({
                type: 'textarea',
                default: ''
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
    _getRouteSuffix() {
        return 'mapicon';
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
                (this.map.getMapState() instanceof EditMapState && this.isEditable()) ||
                (this.map.getMapState() instanceof DeleteMapState && this.isDeletable())
            )
        );
        // // @TODO Refresh the layer; required as a workaround since in mapiconmapobjectgroup we don't know the map_icon_type upon init,
        // // thus we don't know if this will be editable or not. In the sync this will get called and the edit state is known
        // // after which this function will function properly
        // this.onLayerInit();
    }

    localDelete() {
        super.localDelete();

        if (this.linked_awakened_obelisk_polyline !== null) {
            this.linked_awakened_obelisk_polyline.localDelete();
        }
    }

    setLinkedAwakenedObeliskId(linkedAwakenedObeliskId) {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        this.linked_awakened_obelisk_id = linkedAwakenedObeliskId;

        // Delete what we had, always
        if (this.linked_awakened_obelisk_polyline !== null) {
            // This local brushline is a bit different, is not deleted through Leaflet.DRAW which will cause it to not be cleaned up properly
            // The gist is, delete it from the drawn layers to get rid of it (it was already gone from editableLayers)

            this.linked_awakened_obelisk_polyline.localDelete();
            this.linked_awakened_obelisk_polyline = null;
        }

        // Rebuild if necessary
        if (typeof this.linked_awakened_obelisk_id === 'number') {
            let mapIconMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);
            let linkedMapIcon = mapIconMapObjectGroup.findMapObjectById(this.linked_awakened_obelisk_id);

            console.assert(linkedMapIcon !== null, `Unable to find MapIcon for linked_awakened_obelisk_id ${this.linked_awakened_obelisk_id}`, this)

            let brushlineMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_BRUSHLINE);
            this.linked_awakened_obelisk_polyline = brushlineMapObjectGroup.createNewLocalBrushline([{
                lat: linkedMapIcon.lat,
                lng: linkedMapIcon.lng
            }, {
                lat: this.layer.getLatLng().lat,
                lng: this.layer.getLatLng().lng
            }]);
        }
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
        console.assert(this.map_icon_type instanceof MapIconType, 'mapIconType is not a MapIconType', this.map_icon_type);
        return this.map_icon_type;
    }

    isEditable() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        // Admin may edit everything, but not useful when editing a dungeonroute
        return this.map_icon_type.isEditable() && typeof this.linked_awakened_obelisk_id !== 'number';
    }

    isDeletable() {
        return this.map_icon_type.isDeletable();
    }

    bindTooltip() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        this.unbindTooltip();

        if (this.comment.length > 0 || (this.map_icon_type !== null && this.map_icon_type.name.length > 0)) {
            let text = this.comment.length > 0 ? this.comment : this.map_icon_type.name;

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
        return 'Map icon (' + this.comment.substring(0, 25) + ')';
    }

    cleanup() {
        super.cleanup();

        this.map.unregister('map:mapstatechanged', this);
        this.unregister('synced', this);
    }
}