class AdminDungeonFloorSwitchMarker extends DungeonFloorSwitchMarker {

    /**
     *
     * @param map
     * @param layer {L.layer}
     */
    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);

        this.target_floor_id = -1;
    }

    getContextMenuItems() {
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, this, 'this was not an AdminDungeonFloorSwitchMarker');
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

    onLayerInit() {
        // do not call super; we don't want an on-click to redirect us to the target floor
        // super.onLayerInit();
        let self = this;

        let customPopupHtml = $("#dungeon_floor_switch_edit_popup_template").html();
        // Remove template so our
        let template = handlebars.compile(customPopupHtml);

        let data = {
            floors: []
        };
        // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
        let currentFloorId = this.map.getCurrentFloor().id;
        for (let i in this.map.dungeonData.floors) {
            let floor = this.map.dungeonData.floors[i];
            if (floor.id !== currentFloorId) {
                data.floors.push({
                    id: floor.id,
                    name: floor.name,
                });
            }
        }

        let customOptions = {
            'maxWidth': '400',
            'minWidth': '300',
            'className': 'popupCustom'
        };
        // Apply the popup
        this.layer.bindPopup(template(data), customOptions);
        this.layer.on('popupopen', function () {
            $("#dungeon_floor_switch_edit_popup_target_floor").val(self.target_floor_id);

            // Refresh all select pickers so they work again
            let $selectpicker = $(".selectpicker");
            $selectpicker.selectpicker('refresh');
            $selectpicker.selectpicker('render');
            $("#dungeon_floor_switch_edit_popup_submit").on('click', function () {
                self.target_floor_id = $("#dungeon_floor_switch_edit_popup_target_floor").val();

                self.save();
            });
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, this, 'this was not an AdminDungeonFloorSwitchMarker');

        $.ajax({
            type: 'POST',
            url: '/ajax/dungeonfloorswitchmarker',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: self.map.getCurrentFloor().id,
                target_floor_id: self.target_floor_id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            beforeSend: function () {
                self.saving = true;
            },
            success: function (json) {
                self.id = json.id;
                self.setSynced(true);
                self.layer.closePopup();
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

    delete() {
        let self = this;
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, this, 'this was not an AdminDungeonFloorSwitchMarker');
        $.ajax({
            type: 'POST',
            url: '/ajax/dungeonfloorswitchmarker',
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
}