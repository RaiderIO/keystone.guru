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
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);

        this.color = 'pink';
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

    delete() {
        let self = this;
        console.assert(this instanceof Route, this, 'this was not a Route');
        $.ajax({
            type: 'POST',
            url: '/api/v1/route',
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
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                self.setSynced(false);
            }
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof Route, this, 'this was not a Route');
        $.ajax({
            type: 'POST',
            url: '/api/v1/route',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: self.map.getCurrentFloor().id,
                color: self.color,
                vertices: self.getVertices(),
            },
            beforeSend: function () {
                self.saving = true;
                self.layer.setStyle({
                    color: c.map.admin.mapobject.colors.editedBorder
                });
            },
            success: function (json) {
                console.log(json);
                self.id = json.id;
                self.layer.setStyle({
                    color: self.color
                });
                self.setSynced(true);
            },
            complete: function () {
                self.saving = false;
            },
            error: function () {
                self.layer.setStyle({
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);
            }
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof Route, this, 'this is not an Route');
        super.onLayerInit();
    }

    getVertices() {
        let coordinates = this.layer.toGeoJSON().geometry.coordinates;
        console.log(this.layer, this.layer.toGeoJSON(), coordinates);
        let result = [];
        for (let i = 0; i < coordinates.length; i++) {
            result.push({lat: coordinates[i][0], lng: coordinates[i][1]});
        }
        return result;
    }
}