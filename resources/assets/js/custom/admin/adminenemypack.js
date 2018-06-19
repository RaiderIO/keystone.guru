class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
    }

    getContextMenuItems(){
        console.assert(this.constructor.name === 'AdminEnemyPack', this, 'this was not an AdminEnemyPack');
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fa fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: (this.save).bind(this)
        }, '-', {
            text: '<i class="fa fa-remove"></i> ' + (this.saving ? "Deleting.." : "Delete"),
            disabled: !this.synced,
            callback: (this.delete).bind(this)
        }]);
    }

    delete() {
        let self = this;
        console.assert(self.constructor.name === 'AdminEnemyPack', this, 'this was not an AdminEnemyPack');
        $.ajax({
            type: 'POST',
            url: '/api/v1/enemypack',
            dataType: 'json',
            data: {
                _method: 'DELETE',
                id: self.id
            },
            beforeSend: function () {
                console.log("beforeSend");
                self.saving = true;
            },
            success: function (json) {
                self.map.removeEnemyPack(self);
            },
            error: function () {
                console.log("complete");
                self.saving = false;
                self.layer.setStyle({fillColor: c.map.admin.enemypack.colors.unsaved, color: c.map.admin.enemypack.colors.unsavedBorder});
            }
        });
    }

    save() {
        let self = this;
        console.assert(self.constructor.name === 'AdminEnemyPack', this, 'this was not an AdminEnemyPack');
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
                console.log("beforeSend");
                self.saving = true;
                self.layer.setStyle({fillColor: c.map.admin.enemypack.colors.edited, color: c.map.admin.enemypack.colors.editedBorder});
            },
            success: function (json) {
                console.log(json);
                self.id = json.id;
                self.layer.setStyle({fillColor: c.map.admin.enemypack.colors.saved, color: c.map.admin.enemypack.colors.savedBorder});
            },
            error: function () {
                console.log("complete");
                self.saving = false;
                self.layer.setStyle({fillColor: c.map.admin.enemypack.colors.unsaved, color: c.map.admin.enemypack.colors.unsavedBorder});
            }
        });
    }
}