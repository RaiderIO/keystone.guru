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

    isEditable(){
        console.assert(this instanceof MapComment, this, 'this is not a MapComment');
        return !this.always_visible;
    }

    bindTooltip() {
        console.assert(this instanceof MapComment, this, 'this is not a MapComment');

        this.layer.bindTooltip(
            jQuery('<div/>', {
                class: 'map_map_comment_tooltip'
            }).text(this.comment)[0].outerHTML
        ).openTooltip();
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
            },
            success: function (json) {
                self.id = json.id;

                self.setSynced(true);
            },
            complete: function () {
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

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, function (event) {
            // Restore the connections to our enemies

            // let customPopupHtml = $("#killzone_edit_popup_template").html();
            // // Remove template so our
            // let template = handlebars.compile(customPopupHtml);
            //
            // let data = {id: self.id};
            //
            // // Build the status bar from the template
            // customPopupHtml = template(data);
            //
            // let customOptions = {
            //     'maxWidth': '400',
            //     'minWidth': '300',
            //     'className': 'popupCustom'
            // };
            // self.layer.bindPopup(customPopupHtml, customOptions);
            // self.layer.on('popupopen', function (event) {
            //     $("#killzone_edit_popup_color_" + self.id).val(self.killzoneColor);
            //
            //     $("#killzone_edit_popup_submit_" + self.id).bind('click', function () {
            //         self.setMapCommentColor($("#killzone_edit_popup_color_" + self.id).val());
            //
            //         self.edit();
            //     });
            // });
        });
    }

}