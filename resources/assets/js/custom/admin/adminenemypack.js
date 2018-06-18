class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
    }

    getContextMenuItems(){
        console.log("get context menu items");
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fa fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: this.save
        }, {
            text: '<i class="fa fa-remove"></i> ' + (this.saving ? "Deleting.." : "Delete"),
            disabled: !this.synced,
            callback: this.delete
        }]);
    }

    delete() {
        console.log("Delete!");
    }

    save() {
        let self = this;
        $.ajax({
            type: 'POST',
            url: '/api/v1/enemypack',
            dataType: 'json',
            data: {
                id: this.id,
                floor_id: this.map.getCurrentFloor().id,
                label: this.label,
                vertices: this.getVertices()
            },
            beforeSend: function () {
                console.log("beforeSend");
                self.saving = true;
                self.layer.setStyle({fillColor: c.map.admin.enemypack.colors.edited});
            },
            success: function (json) {
                console.log(json);
                self.id = json.id;
                self.layer.setStyle({fillColor: c.map.admin.enemypack.colors.saved});
            },
            complete: function () {
                console.log("complete");
                self.saving = false;
                self.layer.setStyle({fillColor: c.map.admin.enemypack.colors.unsaved});
            }
        });
    }
}