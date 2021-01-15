let LeafletIconUnknown = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown'
});

let LeafletIconUnknownEditMode = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown leaflet-edit-marker-selected'
});

let LeafletIconUnknownDeleteMode = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown leaflet-edit-marker-selected delete'
});

let LeafletIconMarker = L.Marker.extend({
    options: {
        icon: LeafletIconUnknown
    }
});


/**
 * Get the Leaflet Marker that represents said mapIconType
 * @param mapIconType null|obj When null, default unknown marker type is returned
 * @param editModeEnabled bool
 * @param deleteModeEnabled bool
 * @returns {*}
 */
function getLeafletIcon(mapIconType, editModeEnabled, deleteModeEnabled) {
    let icon;
    if (mapIconType === null) {
        console.warn('Unable to find mapIconType for null');
        icon = (editModeEnabled ? LeafletIconUnknownEditMode : (deleteModeEnabled ? LeafletIconUnknownDeleteMode : LeafletIconUnknown));
    } else {
        let template = Handlebars.templates['map_map_icon_visual_template'];

        let width = c.map.mapicon.calculateSize(mapIconType.width);
        let height = c.map.mapicon.calculateSize(mapIconType.height);

        let handlebarsData = $.extend({}, mapIconType, {
            selectedclass: (editModeEnabled ? ' leaflet-edit-marker-selected' : (deleteModeEnabled ? ' leaflet-edit-marker-selected delete' : '')),
            width: width,
            height: height
        });

        icon = L.divIcon({
            html: template(handlebarsData),
            iconSize: [width, height],
            tooltipAnchor: [0, -(height / 2)],
            popupAnchor: [0, -(height / 2)],
            className: 'map_icon_' + mapIconType.key
        });
    }
    return icon;
}

/**
 * @property floor_id int
 * @property map_icon_type_id int
 * @property permanent_tooltip int
 * @property comment string
 */
class Icon extends MapObject {
    constructor(map, layer, options) {
        super(map, layer, options);

        let self = this;

        this.map_icon_type = getState().getMapContext().getUnknownMapIconType();
        this.label = '';

        this.setSynced(false);
        this.register('object:changed', this, this._onObjectChanged.bind(this));
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            if (mapStateChangedEvent.data.previousMapState instanceof EditMapState ||
                mapStateChangedEvent.data.newMapState instanceof EditMapState ||
                mapStateChangedEvent.data.previousMapState instanceof DeleteMapState ||
                mapStateChangedEvent.data.newMapState instanceof DeleteMapState) {
                self._refreshVisual();
            }
        });
        getState().register('mapzoomlevel:changed', this, function (mapStateChangedEvent) {
            self._refreshVisual();
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
        let mapIconTypes = getState().getMapContext().getStaticMapIconTypes();
        let unknownMapIcon = getState().getMapContext().getUnknownMapIconType();

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

    _onObjectChanged() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        // Recreate the tooltip
        this.bindTooltip();
        this._refreshVisual();
    }

    /**
     *
     * @private
     */
    _refreshVisual() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        // Init once or only when visible (as in, only update icons on the same floor)
        if (this.isVisible() || this.layer.getIcon() === LeafletIconUnknown) {
            this.layer.setIcon(
                getLeafletIcon(this.map_icon_type,
                    this.map.getMapState() instanceof EditMapState && this.isEditable(),
                    this.map.getMapState() instanceof DeleteMapState && this.isDeletable()
                )
            );
        }
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
        this.map_icon_type = getState().getMapContext().getMapIconType(this.map_icon_type_id);
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
        return this.map_icon_type.isEditable();
    }

    /**
     * @inheritDoc
     */
    isDeletable() {
        return this.isEditable();
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
                    }).text(text)[0].outerHTML, $.extend({
                        direction: 'top'
                    }, this.getTooltipOptions())
                );
            } else {
                this.layer.bindTooltip(text, $.extend({
                    direction: 'top'
                }, this.getTooltipOptions()));
            }
        }
    }

    getTooltipOptions(){
        return {};
    }

    toString() {
        return `Map icon (${this.comment.substring(0, 25)})`;
    }

    cleanup() {
        super.cleanup();

        getState().unregister('mapzoomlevel:changed', this);
        this.map.unregister('map:mapstatechanged', this);
        this.unregister('object:changed', this);
    }
}