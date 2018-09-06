$(function () {
    L.Draw.Route = L.Draw.Polyline.extend({
        statics: {
            TYPE: 'route'
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.Route.TYPE;
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

class Route extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Route';
        this.saving = false;
        this.deleting = false;

        this.setColor(c.map.route.defaultColor);
        this.setSynced(false);
    }

    setColor(color) {
        this.routeColor = color;
        this.setColors({
            unsavedBorder: color,
            unsaved: color,

            editedBorder: color,
            edited: color,

            savedBorder: color,
            saved: color
        });
    }

    getContextMenuItems() {
        console.assert(this instanceof Route, this, 'this was not a Route');
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

    edit() {
        let self = this;
        console.assert(this instanceof Route, this, 'this was not a Route');

        $.ajax({
            type: 'POST',
            url: '/ajax/route',
            dataType: 'json',
            data: {
                id: self.id,
                dungeonroute: dungeonRoutePublicKey, // defined in map.blade.php
                floor_id: self.map.getCurrentFloor().id,
                color: self.routeColor,
                vertices: self.getVertices(),
            },
            beforeSend: function () {
                self.editing = true;
                $("#route_edit_popup_submit").attr('disabled', 'disabled');
            },
            success: function (json) {
                self.setSynced(true);
                self.map.leafletMap.closePopup();
            },
            complete: function () {
                $("#route_edit_popup_submit").removeAttr('disabled');
                self.editing = false;
            },
            error: function () {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);
            }
        });
    }

    delete() {
        let self = this;
        console.assert(this instanceof Route, this, 'this was not a Route');
        $.ajax({
            type: 'POST',
            url: '/ajax/route',
            dataType: 'json',
            data: {
                _method: 'DELETE',
                id: self.id
            },
            beforeSend: function () {
                self.deleting = true;
            },
            success: function (json) {
                self.signal('object:deleted', {response: json});
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
        console.assert(this instanceof Route, this, 'this was not a Route');
        $.ajax({
            type: 'POST',
            url: '/ajax/route',
            dataType: 'json',
            data: {
                id: self.id,
                dungeonroute: dungeonRoutePublicKey, // defined in map.blade.php
                floor_id: self.map.getCurrentFloor().id,
                color: self.routeColor,
                vertices: self.getVertices(),
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
        console.assert(this instanceof Route, this, 'this is not an Route');
        super.onLayerInit();

        let self = this;

        // Only when we're editing
        if( this.map.edit ){
            // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
            self.register('synced', this, function (event) {
                let customPopupHtml = $("#route_edit_popup_template").html();
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
                self.layer.bindPopup(customPopupHtml, customOptions);
                self.layer.on('popupopen', function (event) {
                    $("#route_edit_popup_color_" + self.id).val(self.routeColor);

                    $("#route_edit_popup_submit_" + self.id).bind('click', function () {
                        self.setColor($("#route_edit_popup_color_" + self.id).val());

                        self.edit();
                    });
                });
            });
        }
    }

    getVertices() {
        let coordinates = this.layer.toGeoJSON().geometry.coordinates;
        let result = [];
        for (let i = 0; i < coordinates.length; i++) {
            result.push({lat: coordinates[i][0], lng: coordinates[i][1]});
        }
        return result;
    }
}