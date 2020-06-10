class AdminMapIcon extends MapIcon {

    // Actually this class is quite empty. But I'll have it anyways for any possible later additions.
    constructor(map, layer) {
        super(map, layer);
    }

    delete() {
        let self = this;
        console.assert(this instanceof MapIcon, 'this was not a MapIcon', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/mapicon/' + this.id,
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
            url: '/ajax/mapicon',
            dataType: 'json',
            data: {
                id: this.id,
                floor_id: getState().getCurrentFloor().id,
                map_icon_type_id: this.map_icon_type_id,
                comment: this.comment,
                seasonal_index: this.seasonal_index,
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
}