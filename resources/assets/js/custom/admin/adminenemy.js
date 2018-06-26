class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
    }

    onLayerInit(){
        super.onLayerInit();

        let customPopup = $("#enemy_edit_popup").html();
        let customOptions =
        {
            'maxWidth': '400',
            'width': '200',
            'className' : 'popupCustom'
        };
        this.layer.bindPopup(customPopup, customOptions);
    }

    getContextMenuItems() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        console.log("test");
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
        console.log("starting edit");

        // Bind popup
        this.layer.openPopup();
    }

    doEdit() {

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
                    fillColor: c.map.admin.enemypack.colors.unsaved,
                    color: c.map.admin.enemypack.colors.unsavedBorder
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
                    fillColor: c.map.admin.enemypack.colors.edited,
                    color: c.map.admin.enemypack.colors.editedBorder
                });
            },
            success: function (json) {
                console.log(json);
                self.id = json.id;
                self.layer.setStyle({
                    fillColor: c.map.admin.enemypack.colors.saved,
                    color: c.map.admin.enemypack.colors.savedBorder
                });
            },
            complete: function () {
                self.saving = false;
            },
            error: function () {
                self.layer.setStyle({
                    fillColor: c.map.admin.enemypack.colors.unsaved,
                    color: c.map.admin.enemypack.colors.unsavedBorder
                });
            }
        });
    }
}