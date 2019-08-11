class AdminDungeonStartMarker extends DungeonStartMarker {

    constructor(map, layer) {
        super(map, layer);

        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminDungeonStartMarker, 'this was not an AdminDungeonStartMarker', this);
        $.ajax({
            type: 'POST',
            url: '/ajax/dungeonstartmarker',
            dataType: 'json',
            data: {
                _method: 'DELETE',
                id: self.id
            },
            success: function (json) {
                self.localDelete();
            },
            error: function (xhr, textStatus, errorThrown) {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminDungeonStartMarker, 'this was not an AdminDungeonStartMarker', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/dungeonstartmarker',
            dataType: 'json',
            data: {
                floor_id: getState().getCurrentFloor().id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            success: function (json) {
                self.id = json.id;
                self.setSynced(true);
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }
}