class AdminDungeonFloorSwitchMarker extends DungeonFloorSwitchMarker {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
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
        super.onLayerInit();
        let self = this;

        let customPopupHtml = $("#dungeon_floor_select_edit_popup_template").html();
        // Remove template so our
        let template = handlebars.compile(customPopupHtml);

        let data = {
            floors: []
        };
        let currentFloorId = this.map.getCurrentFloor().id;
        for (let i in this.map.dungeonData[0].floors) {
            let floor = this.map.dungeonData[0].floors[i];
            console.log(floor, currentFloorId);
            if (floor.id !== currentFloorId) {
                data.floors.push({
                    id: floor.id,
                    name: floor.name,
                });
            }
        }

        // Build the status bar from the template
        customPopupHtml = template(data);

        let customOptions = {
            'maxWidth': '400',
            'minWidth': '300',
            'className': 'popupCustom'
        };
        this.layer.bindPopup(customPopupHtml, customOptions);
        this.layer.on('popupopen', function(){
            // Refresh all select pickers so they work again
            $(".selectpicker").selectpicker('refresh');
            $(".selectpicker").selectpicker('render');
            $("#enemy_edit_popup_submit").on('click', function () {
                self.npc_id = $("#enemy_edit_popup_npc").val();

                self.edit();
            });
        });
    }

    // edit() {
    //     let self = this;
    //     console.assert(this instanceof AdminDungeonFloorSwitchMarker, this, 'this was not an AdminDungeonFloorSwitchMarker');
    //     $.ajax({
    //         type: 'POST',
    //         url: '/api/v1/floorSwitchMarker',
    //         dataType: 'json',
    //         data: {
    //             _method: 'PATCH',
    //             id: self.id,
    //             floor_id: self.map.getCurrentFloor().id,
    //             lat: self.layer.getLatLng().lat,
    //             lng: self.layer.getLatLng().lng
    //         },
    //         beforeSend: function () {
    //             self.editing = true;
    //         },
    //         success: function (json) {
    //             self.setSynced(true);
    //             self.layer.closePopup();
    //         },
    //         complete: function () {
    //             self.editing = false;
    //         },
    //         error: function () {
    //             // Even if we were synced, make sure user knows it's no longer / an error occurred
    //             self.setSynced(false);
    //         }
    //     });
    // }
    //
    delete() {
        let self = this;
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, this, 'this was not an AdminDungeonFloorSwitchMarker');
        $.ajax({
            type: 'POST',
            url: '/api/v1/floorswitchmarker',
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
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, this, 'this was not an AdminDungeonFloorSwitchMarker');

        $.ajax({
            type: 'POST',
            url: '/api/v1/floorswitchmarker',
            dataType: 'json',
            data: {
                floor_id: self.map.getCurrentFloor().id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            beforeSend: function () {
                self.saving = true;
            },
            success: function (json) {
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