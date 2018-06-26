class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
    }

    onLayerInit(){
        super.onLayerInit();

        let customPopup = $("#enemy_edit_popup").children();
        // Remove template so our
        customPopup = customPopup.replace('_template', '');

        let customOptions =
        {
            'maxWidth': '400',
            'minWidth': '300',
            'className' : 'popupCustom'
        };
        this.layer.bindPopup(customPopup, customOptions);
    }

    getContextMenuItems() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fa fa-pencil"></i> ' + (this.editing ? "Editing.." : "Edit"),
            disabled: this.synced || this.editing,
            callback: (this.startEdit).bind(this)
        }, {
            text: '<i class="fa fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: (this.save).bind(this)
        }, '-', {
            text: '<i class="fa fa-remove"></i> ' + (this.deleting ? "Deleting.." : "Delete"),
            disabled: !this.synced || this.deleting,
            callback: (this.delete).bind(this)
        }]);
    }

    startEdit() {
        console.assert(this instanceof AdminEnemy, this, 'this is not an AdminEnemy');
        console.log("starting edit");
        let self = this;

        // Bind popup
        this.layer.openPopup();
        $("#enemy_edit_popup_submit").on('click', function(){
            self.npc_id = $("#enemy_edit_popup_npc").val();

            self.edit();
        });
    }

    edit() {
        let self = this;
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        $.ajax({
            type: 'POST',
            url: '/api/v1/enemy',
            dataType: 'json',
            data: {
                _method: 'UPDATE',
                id: self.id,
                npc_id: self.npc_id,
                x: self.x,
                y: self.y,
                enemypack: self.enemypack
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
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
            }
        });
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        $.ajax({
            type: 'POST',
            url: '/api/v1/enemypack',
            dataType: 'json',
            data: {
                _method: 'DELETE',
                id: self.id
            },
            beforeSend: function () {
                self.deleting = true;
            },
            success: function (json) {
                self.map.removeEnemyPack(self);
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
            url: '/api/v1/enemypack',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: self.map.getCurrentFloor().id,
                label: self.label,
                vertices: self.getVertices(),
                data: JSON.stringify(self.layer.toGeoJSON())
            },
            beforeSend: function () {
                self.saving = true;
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.edited,
                    color: c.map.admin.mapobject.colors.editedBorder
                });
            },
            success: function (json) {
                console.log(json);
                self.id = json.id;
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.saved,
                    color: c.map.admin.mapobject.colors.savedBorder
                });
            },
            complete: function () {
                self.saving = false;
            },
            error: function () {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
            }
        });
    }
}