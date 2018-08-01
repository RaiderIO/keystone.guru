$(function () {
    L.Draw.Enemy = L.Draw.CircleMarker.extend({
        statics: {
            TYPE: 'enemy'
        },
        options: {},
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.Enemy.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.npc_id = 0;
        this.enemypack = 0;

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
    }

    onLayerInit() {
        super.onLayerInit();

        let customPopup = $("#enemy_edit_popup").html();
        // Remove template so our
        customPopup = customPopup.replace('_template', '');

        let customOptions = {
            'maxWidth': '400',
            'minWidth': '300',
            'className': 'popupCustom'
        };
        this.layer.bindPopup(customPopup, customOptions);
    }

    getContextMenuItems() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fas fa-pencil"></i> ' + (this.editing ? "Editing.." : "Edit"),
            disabled: this.editing,
            callback: (this.startEdit).bind(this)
        }, {
            text: '<i class="fas fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: (this.save).bind(this)
        }, '-', {
            text: '<i class="fas fa-remove"></i> ' + (this.deleting ? "Deleting.." : "Delete"),
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
        // Refresh all select pickers so they work again
        $(".selectpicker").selectpicker('refresh');
        $("#enemy_edit_popup_submit").on('click', function () {
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
                id: self.id,
                npc_id: self.npc_id,
                floor_id: self.map.getCurrentFloor().id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng,
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
            url: '/api/v1/enemy',
            dataType: 'json',
            data: {
                id: self.id,
                enemy_pack_id: self.enemypack.id,
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