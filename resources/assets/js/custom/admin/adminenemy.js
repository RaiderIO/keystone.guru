class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.npc_id = 0;
        // Init to an empty value
        this.enemy_pack_id = -1;

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
    }

    onLayerInit() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        super.onLayerInit();
        let self = this;

        let customPopupHtml = $("#enemy_edit_popup_template").html();
        // Remove template so our
        let template = handlebars.compile(customPopupHtml);

        // No data in this template
        let data = {};

        // Build the status bar from the template
        customPopupHtml = template(data);

        let customOptions = {
            'maxWidth': '400',
            'minWidth': '300',
            'className': 'popupCustom'
        };
        this.layer.bindPopup(customPopupHtml, customOptions);
        this.layer.on('popupopen', function () {
            $("#enemy_edit_popup_npc").val(self.npc_id);

            // Refresh all select pickers so they work again
            let $selectpicker = $(".selectpicker");
            $selectpicker.selectpicker('refresh');
            $selectpicker.selectpicker('render');

            $("#enemy_edit_popup_submit").bind('click', function () {
                console.log('test');
                self.npc_id = $("#enemy_edit_popup_npc").val();

                self.edit();
            });
        })
    }

    getContextMenuItems() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
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
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        $.ajax({
            type: 'POST',
            url: '/api/v1/enemy',
            dataType: 'json',
            data: {
                id: self.id,
                enemy_pack_id: self.enemy_pack_id,
                npc_id: self.npc_id,
                floor_id: self.map.getCurrentFloor().id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            beforeSend: function () {
                self.editing = true;
                $("#enemy_edit_popup_submit").attr('disabled', 'disabled');
            },
            success: function (json) {
                self.setSynced(true);
                self.layer.closePopup();
            },
            complete: function () {
                $("#enemy_edit_popup_submit").removeAttr('disabled');
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
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        $.ajax({
            type: 'POST',
            url: '/api/v1/enemy',
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
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');

        $.ajax({
            type: 'POST',
            url: '/api/v1/enemy',
            dataType: 'json',
            data: {
                id: self.id,
                enemy_pack_id: self.enemy_pack_id,
                npc_id: self.npc_id,
                floor_id: self.map.getCurrentFloor().id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            beforeSend: function () {
                self.saving = true;
            },
            success: function (json) {
                console.log(json);
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