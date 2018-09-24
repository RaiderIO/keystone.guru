$(function () {
    L.Draw.MapComment = L.Draw.Marker.extend({
        statics: {
            TYPE: 'mapcomment'
        },
        options: {
            icon: LeafletMapCommentIcon
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.MapComment.TYPE;
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

let LeafletMapCommentIcon = L.divIcon({
    html: '<i class="fas fa-comment"></i>',
    iconSize: [16, 16],
    className: 'marker_div_icon_font_awesome marker_div_icon_mapcomment'
});

let LeafletMapCommentMarker = L.Marker.extend({
    options: {
        icon: LeafletMapCommentIcon
    }
});

class MapComment extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.id = 0;
        this.label = 'MapComment';
        this.always_visible = false;
        this.saving = false;
        this.deleting = false;

        this.setSynced(false);
    }

    getContextMenuItems() {
        console.assert(this instanceof MapComment, this, 'this was not a MapComment');
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fas fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: (this.save).bind(this)
        }, '-', {
            text: '<i class="fas fa-trash"></i> ' + (this.deleting ? "Deleting.." : "Delete"),
            disabled: !this.synced || this.deleting,
            callback: (this.delete).bind(this)
        }]);
    }

    isEditable() {
        console.assert(this instanceof MapComment, this, 'this is not a MapComment');
        return !this.always_visible;
    }

    bindTooltip() {
        console.assert(this instanceof MapComment, this, 'this is not a MapComment');

        this.layer.bindTooltip(
            jQuery('<div/>', {
                class: 'map_map_comment_tooltip'
            }).text(this.comment)[0].outerHTML
        );
    }

    edit() {
        console.assert(this instanceof MapComment, this, 'this was not a MapComment');
        this.save();
    }

    delete() {
        let self = this;
        console.assert(this instanceof MapComment, this, 'this was not a MapComment');
        $.ajax({
            type: 'POST',
            url: '/ajax/mapcomment',
            dataType: 'json',
            data: {
                _method: 'DELETE',
                id: self.id
            },
            beforeSend: function () {
                self.deleting = true;
            },
            success: function (json) {

            },
            complete: function () {
                self.deleting = false;
            },
            error: function () {
                self.setSynced(false);
            }
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof MapComment, this, 'this was not a MapComment');
        $.ajax({
            type: 'POST',
            url: '/ajax/mapcomment',
            dataType: 'json',
            data: {
                id: self.id,
                dungeonroute: dungeonRoutePublicKey, // defined in map.blade.php
                floor_id: self.map.getCurrentFloor().id,
                comment: self.comment,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng,
            },
            beforeSend: function () {
                self.saving = true;
                $('#map_map_comment_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
            },
            success: function (json) {
                self.id = json.id;

                self.setSynced(true);
                self.map.leafletMap.closePopup();
            },
            complete: function () {
                $('#map_map_comment_edit_popup_submit_' + self.id).removeAttr('disabled');
                self.saving = false;
            },
            error: function () {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);
            }
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof MapComment, this, 'this is not an MapComment');
        super.onLayerInit();

        let self = this;

        // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
        // This also cannot be a private function since that'll apparently give different signatures as well.
        let popupOpenFn = function (event) {
            $('#map_map_comment_edit_popup_comment_' + self.id).val(self.comment);

            // Prevent multiple binds to click
            let $submitBtn = $('#map_map_comment_edit_popup_submit_' + self.id);
            $submitBtn.unbind('click');
            $submitBtn.bind('click', function () {
                self.comment = $('#map_map_comment_edit_popup_comment_' + self.id).val();

                self.edit();
            });
        };

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, function (event) {
            let customPopupHtml = $('#map_map_comment_edit_popup_template').html();
            // Remove template so our
            let template = handlebars.compile(customPopupHtml);

            let data = {id: self.id};

            // Build the status bar from the template
            customPopupHtml = template(data);

            let customOptions = {
                'maxWidth': '400',
                'minWidth': '300',
                'className': 'popupCustom'
            };

            self.layer.unbindPopup();
            self.layer.bindPopup(customPopupHtml, customOptions);

            self.layer.off('popupopen', popupOpenFn);
            self.layer.on('popupopen', popupOpenFn);
        });
    }

}