class AdminDungeonFloorSwitchMarker extends DungeonFloorSwitchMarker {

    /**
     *
     * @param map
     * @param layer {L.layer}
     */
    constructor(map, layer) {
        super(map, layer);

        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);

        this.target_floor_id = -1;
    }

    onLayerInit() {
        // do not call super; we don't want an on-click to redirect us to the target floor
        // super.onLayerInit();
        let self = this;

        // Remove template so our
        let template = Handlebars.templates['map_dungeon_floor_switch_template'];

        let data = $.extend({
            floors: []
        }, getHandlebarsDefaultVariables());

        // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
        let currentFloorId = getState().getCurrentFloor().id;
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
        this.layer.unbindPopup();
        this.layer.bindPopup(template(data), customOptions);

        let fn = function () {
            $("#dungeon_floor_switch_edit_popup_target_floor").val(self.target_floor_id);

            // Refresh all select pickers so they work again
            refreshSelectPickers();

            let $submitBtn = $("#dungeon_floor_switch_edit_popup_submit");
            $submitBtn.unbind('click');
            $submitBtn.bind('click', function () {
                self.target_floor_id = $("#dungeon_floor_switch_edit_popup_target_floor").val();

                self.save();
            });
        };

        this.layer.off('popupopen');
        this.layer.on('popupopen', fn);
    }

    edit() {
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, 'this was not an AdminDungeonFloorSwitchMarker', this);
        this.save();
    }

    save() {
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, 'this was not an AdminDungeonFloorSwitchMarker', this);
        let self = this;

        $.ajax({
            type: 'POST',
            url: '/ajax/dungeonfloorswitchmarker',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: getState().getCurrentFloor().id,
                target_floor_id: self.target_floor_id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            success: function (json) {
                self.id = json.id;
                self.setSynced(true);
                self.layer.closePopup();
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminDungeonFloorSwitchMarker, 'this was not an AdminDungeonFloorSwitchMarker', this);
        $.ajax({
            type: 'POST',
            url: '/ajax/dungeonfloorswitchmarker/' + self.id,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            success: function (json) {
                self.localDelete();
            },
            error: function (xhr, textStatus, errorThrown) {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }
}