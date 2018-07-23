class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
    }

    getContextMenuItems() {
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fa fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: (this.save).bind(this)
        }, '-', {
            text: '<i class="fa fa-remove"></i> ' + (this.deleting ? "Deleting.." : "Delete"),
            disabled: !this.synced || this.deleting,
            callback: (this.delete).bind(this)
        }]);
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
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
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
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