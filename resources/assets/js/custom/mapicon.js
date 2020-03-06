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

function getLeafletMapIconMarker(mapIconType){
    let generatedMarker = L.Marker.extend({
        options: {
            icon: L.divIcon({
                html: '<div class="' + mapIconType.key + '"><img src="/images/mapicon/' + mapIconType.key + '.png" /></div>',
                iconSize: [16, 16],
                className: 'enemy_icon ' + 'map_icon_' + mapIconType.key
            })
        }
    });
    return new generatedMarker();
}

let LeafletMapIconComment = L.divIcon({
    html: '<i class="fas fa-comment"></i>',
    iconSize: [16, 16],
    className: 'marker_div_icon_font_awesome marker_div_icon_mapcomment'
});

let LeafletMapIconUnknown = L.divIcon({
    html: '<i class="fas fa-question"></i>',
    iconSize: [16, 16],
    className: 'marker_div_icon_font_awesome marker_div_icon_mapcomment'
});

let LeafletMapIconGreaseBot = L.divIcon({
    html: '<i class="fas fa-comment"></i>',
    iconSize: [16, 16],
    className: 'marker_div_icon_font_awesome marker_div_icon_mapcomment'
});

let LeafletMapIconShockBot = L.divIcon({
    html: '<i class="fas fa-shock"></i>',
    iconSize: [16, 16],
    className: 'marker_div_icon_font_awesome marker_div_icon_mapcomment'
});

let LeafletMapIconWeldingBot = L.divIcon({
    html: '<i class="fas fa-plus"></i>',
    iconSize: [16, 16],
    className: 'marker_div_icon_font_awesome marker_div_icon_mapcomment'
});

let LeafletMapIconCommentMarker = L.Marker.extend({
    options: {
        icon: LeafletMapIconComment
    }
});

let LeafletMapIconUnknownMarker = L.Marker.extend({
    options: {
        icon: LeafletMapIconUnknown
    }
});

class MapIcon extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.id = 0;
        this.map_icon_type_id = 0;
        this.label = 'MapIcon';

        this.setSynced(false);
    }

    _popupSubmitClicked() {
        console.assert(this instanceof MapIcon, 'this was not a MapIcon', this);
        this.comment = $('#map_map_icon_edit_popup_comment_' + this.id).val();
        this.map_icon_type_id = $('#map_map_icon_edit_popup_icon_type_id_' + this.id).val();

        this.edit();
    }

    isEditable() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        return !this.always_visible;
    }

    bindTooltip() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        this.layer.bindTooltip(
            jQuery('<div/>', {
                class: 'map_map_comment_tooltip'
            }).text(this.comment)[0].outerHTML
        );
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
            url: '/ajax/' + this.map.getDungeonRoute().publicKey + '/mapicon/' + this.id,
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
            url: '/ajax/' + this.map.getDungeonRoute().publicKey + '/mapicon',
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
                for( let i in MAP_ICON_TYPES ){
                    if( MAP_ICON_TYPES.hasOwnProperty(i) ){
                        let template = Handlebars.templates['map_map_icon_select_option_template'];

                        MAP_ICON_TYPES[i].html = template(MAP_ICON_TYPES[i]);
                    }
                }

                let data = $.extend({id: self.id, map_icon_type_id: self.map_icon_type_id, mapicontypes: MAP_ICON_TYPES}, getHandlebarsDefaultVariables());

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

}