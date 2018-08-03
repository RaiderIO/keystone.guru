class AdminDungeonStartMarker extends DungeonStartMarker {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
    }

    getContextMenuItems() {
        console.assert(this instanceof AdminDungeonStartMarker, this, 'this was not an AdminDungeonStartMarker');
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fas fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: (this.save).bind(this)
        }, '-', {
            text: '<i class="fas fa-remove"></i> ' + (this.deleting ? "Deleting.." : "Delete"),
            disabled: !this.synced || this.deleting,
            callback: (this.delete).bind(this)
        }]);
    }

    // edit() {
    //     let self = this;
    //     console.assert(this instanceof AdminDungeonStartMarker, this, 'this was not an AdminDungeonStartMarker');
    //     $.ajax({
    //         type: 'POST',
    //         url: '/api/v1/dungeonstartmarker',
    //         dataType: 'json',
    //         data: {
    //             _method: 'PATCH',
    //             id: self.id,
    //             floor_id: self.map.getCurrentFloor().id,
    //             lat: self.layer.getLatLng().lat,
    //             lng: self.layer.getLatLng().lng
    //         },
    //         beforeSend: function () {
    //             self.editing = true;
    //         },
    //         success: function (json) {
    //             self.setSynced(true);
    //             self.layer.closePopup();
    //         },
    //         complete: function () {
    //             self.editing = false;
    //         },
    //         error: function () {
    //             // Even if we were synced, make sure user knows it's no longer / an error occurred
    //             self.setSynced(false);
    //         }
    //     });
    // }
    //
    delete() {
        let self = this;
        console.assert(this instanceof AdminDungeonStartMarker, this, 'this was not an AdminDungeonStartMarker');
        $.ajax({
            type: 'POST',
            url: '/api/v1/dungeonstartmarker',
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
            }
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminDungeonStartMarker, this, 'this was not an AdminDungeonStartMarker');

        $.ajax({
            type: 'POST',
            url: '/api/v1/dungeonstartmarker',
            dataType: 'json',
            data: {
                floor_id: self.map.getCurrentFloor().id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
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
}