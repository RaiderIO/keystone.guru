class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.synced = false;
        this.saving = false;
    }

    onLayerInit() {
        // Create the context menu items
        let contextMenuItems = [{
            text: this.label,
            disabled: true
        }, {
            text: '<i class="fa fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: this.save
        }];

        // Create the context menu
        this.layer.bindContextMenu({
            contextmenuWidth: 140,
            contextmenuItems: contextMenuItems
        });

        // Show a permantent tooltip for the pack's name
        this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
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