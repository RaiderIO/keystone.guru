class AdminMapComment extends MapComment {

    // Actually this class is quite empty. But I'll have it anyways for any possible later additions.
    constructor(map, layer) {
        super(map, layer);
    }

    _popupSubmitClicked(){
        console.assert(this instanceof AdminMapComment, 'this was not a MapComment', this);
        // Set an additional parameter
        this.always_visible = $('#map_map_comment_edit_popup_always_visible_' + this.id).val();

        // Now the rest and submit
        super._popupSubmitClicked();
    }



    delete() {
        let self = this;
        console.assert(this instanceof MapComment, 'this was not a MapComment', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/mapcomment/' + this.id,
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
        console.assert(this instanceof MapComment, 'this was not a MapComment', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/mapcomment',
            dataType: 'json',
            data: {
                id: this.id,
                floor_id: getState().getCurrentFloor().id,
                comment: this.comment,
                lat: this.layer.getLatLng().lat,
                lng: this.layer.getLatLng().lng,
            },
            beforeSend: function () {
                $('#map_map_comment_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
            },
            success: function (json) {
                self.id = json.id;

                self.setSynced(true);
                self.map.leafletMap.closePopup();
            },
            complete: function () {
                $('#map_map_comment_edit_popup_submit_' + self.id).removeAttr('disabled');
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }
}