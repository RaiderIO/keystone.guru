class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.npc_id = 0;
        // Init to an empty value
        this.enemy_pack_id = -1;
        this.teeming = '';
        // Filled when we're currently drawing a patrol line
        this.currentPatrolPolyline = null;

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
    }

    onLayerInit() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        super.onLayerInit();
        let self = this;

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        self.register('synced', function(event){
            let customPopupHtml = $("#enemy_edit_popup_template").html();
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
                console.log(event);

                $("#enemy_edit_popup_attached_to_pack_" + self.id).text(self.enemy_pack_id >= 0 ? 'true' : 'false');
                $("#enemy_edit_popup_npc_" + self.id).val(self.npc_id);
                $("#enemy_edit_popup_teeming_" + self.id).val(self.teeming);

                // Refresh all select pickers so they work again
                let $selectpicker = $(".selectpicker");
                $selectpicker.selectpicker('refresh');
                $selectpicker.selectpicker('render');

                $("#enemy_edit_popup_submit").bind('click', function () {
                    self.npc_id = $("#enemy_edit_popup_npc_" + self.id).val();
                    self.teeming = $("#enemy_edit_popup_teeming_" + self.id).val();

                    self.edit();
                });
            });
        });

        self.map.leafletMap.on('contextmenu', function(){
            if( self.currentPatrolPolyline !== null ){
                console.log('currentPatrol: ', self.currentPatrolPolyline);
                self.map.leafletMap.addLayer(self.currentPatrolPolyline);
                self.currentPatrolPolyline.disable();
            }
        });
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
            url: '/ajax/enemy',
            dataType: 'json',
            data: {
                id: self.id,
                enemy_pack_id: self.enemy_pack_id,
                npc_id: self.npc_id,
                floor_id: self.map.getCurrentFloor().id,
                teeming: self.teeming,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            beforeSend: function () {
                self.editing = true;
                $("#enemy_edit_popup_submit").attr('disabled', 'disabled');
            },
            success: function (json) {
                self.setSynced(true);
                self.map.leafletMap.closePopup();
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
            url: '/ajax/enemy',
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
            url: '/ajax/enemy',
            dataType: 'json',
            data: {
                id: self.id,
                enemy_pack_id: self.enemy_pack_id,
                npc_id: self.npc_id,
                floor_id: self.map.getCurrentFloor().id,
                teeming: self.teeming,
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