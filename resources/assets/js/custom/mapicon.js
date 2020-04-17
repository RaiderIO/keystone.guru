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

        let handlebarsData = $.extend(mapIconType, {
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

class MapIcon extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.id = 0;
        this.map_icon_type_id = 0;
        this.map_icon_type = getState().getUnknownMapIcon();
        this.has_dungeon_route = false;
        this.comment = '';
        this.label = 'MapIcon';

        this.setSynced(false);
        this.register('synced', this, this._synced.bind(this));
        this.map.register('map:editmodetoggled', this, this._refreshVisual.bind(this))
    }

    _synced() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        // Recreate the tooltip
        this.bindTooltip();
        this._refreshVisual();
    }

    _popupSubmitClicked() {
        console.assert(this instanceof MapIcon, 'this was not a MapIcon', this);
        this.comment = $('#map_map_icon_edit_popup_comment_' + this.id).val();
        this.map_icon_type_id = parseInt($('#map_map_icon_edit_popup_map_icon_type_id_' + this.id).val());
        this.setMapIconType(getState().getMapIconType(this.map_icon_type_id));

        this.edit();
    }

    _refreshVisual() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        this.layer.setIcon(getMapIconLeafletIcon(this.map_icon_type, this.map.editModeActive && this.isEditable()));
        // // @TODO Refresh the layer; required as a workaround since in mapiconmapobjectgroup we don't know the map_icon_type upon init,
        // // thus we don't know if this will be editable or not. In the sync this will get called and the edit state is known
        // // after which this function will function properly
        // this.onLayerInit();
    }

    setMapIconType(mapIconType) {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        console.assert(mapIconType instanceof MapIconType, 'mapIconType is not a MapIconType', mapIconType);

        this.map_icon_type = mapIconType;
    }

    isEditable() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        // Admin may edit everything, but not useful when editing a dungeonroute
        return this.map_icon_type.isEditable();
    }

    isDeletable() {
        return this.map_icon_type.isDeletable();
    }

    bindTooltip() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        this.unbindTooltip();

        if (this.comment.length > 0 || (this.map_icon_type !== null && this.map_icon_type.name.length > 0)) {
            this.layer.bindTooltip(this.comment.length > 0 ? this.comment : this.map_icon_type.name, {
                    direction: 'top'
                }
            );
        }
    }

    edit() {
        console.assert(this instanceof MapIcon, 'this was not a MapIcon', this);
        this.save();
    }

    delete() {
        let self = this;
        console.assert(this instanceof MapIcon, 'this was not a MapIcon', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/' + getState().getDungeonRoute().publicKey + '/mapicon/' + this.id,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            success: function (json) {
                self.localDelete();
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof MapIcon, 'this was not a MapIcon', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/' + getState().getDungeonRoute().publicKey + '/mapicon',
            dataType: 'json',
            data: {
                id: this.id,
                floor_id: getState().getCurrentFloor().id,
                map_icon_type_id: this.map_icon_type_id,
                comment: this.comment,
                lat: this.layer.getLatLng().lat,
                lng: this.layer.getLatLng().lng,
            },
            beforeSend: function () {
                $('#map_map_icon_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
            },
            success: function (json) {
                self.id = json.id;

                self.setSynced(true);
                self.map.leafletMap.closePopup();
            },
            complete: function () {
                $('#map_map_icon_edit_popup_submit_' + self.id).removeAttr('disabled');
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof MapIcon, 'this is not an MapIcon', this);
        super.onLayerInit();

        let self = this;

        if (this.isEditable() && this.map.options.edit) {
            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well.
            let popupOpenFn = function (event) {
                $('#map_map_icon_edit_popup_comment_' + self.id).val(self.comment);
                $('#map_map_icon_edit_popup_map_icon_type_id_' + self.id).val(self.map_icon_type_id);

                // Prevent multiple binds to click
                let $submitBtn = $('#map_map_icon_edit_popup_submit_' + self.id);
                $submitBtn.unbind('click');
                $submitBtn.bind('click', self._popupSubmitClicked.bind(self));

                refreshSelectPickers();
            };

            // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
            this.register('synced', this, function (event) {
                let template = Handlebars.templates['map_map_icon_edit_popup_template'];

                // Construct the html for each option and insert it into the handlebars template
                let mapIconTypes = getState().getMapIconTypes();
                let unknownMapIcon = getState().getUnknownMapIcon();

                let editableMapIconTypes = [];
                for (let i in mapIconTypes) {
                    // Only editable types!
                    if (mapIconTypes.hasOwnProperty(i) && mapIconTypes[i].isEditable()) {
                        // Skip unknown map icons, that should be a one time state when placing the icon, not a selectable state
                        if (mapIconTypes[i].id !== unknownMapIcon.id) {
                            let template = Handlebars.templates['map_map_icon_select_option_template'];

                            // Direct assign to the object that is in the array so we're sure this change sticks
                            mapIconTypes[i].html = template(mapIconTypes[i]);

                            editableMapIconTypes.push(mapIconTypes[i]);
                        }
                    }
                }

                let data = $.extend({
                    id: self.id,
                    map_icon_type_id: self.map_icon_type_id,
                    mapicontypes: editableMapIconTypes
                }, getHandlebarsDefaultVariables());

                self.layer.unbindPopup();
                self.layer.bindPopup(template(data), {
                    'maxWidth': '400',
                    'minWidth': '300',
                    'className': 'popupCustom'
                });

                self.layer.off('popupopen');
                self.layer.on('popupopen', popupOpenFn);
            });

        }
    }

    cleanup() {
        super.cleanup();

        this.map.unregister('map:editmodetoggled', this);
    }

}