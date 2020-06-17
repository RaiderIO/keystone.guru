class AdminEnemyPatrol extends EnemyPatrol {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        this.enemy_id = -1;
    }

    onLayerInit() {
        console.assert(this instanceof AdminEnemyPatrol, 'this was not an AdminEnemyPatrol', this);
        super.onLayerInit();

        this.onPopupInit();
    }

    onPopupInit() {
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, 'this was not an AdminEnemyPatrol', this);

        // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
        // This also cannot be a private function since that'll apparently give different signatures as well.
        let popupOpenFn = function (event) {
            $('#enemy_patrol_edit_popup_teeming_' + self.id).val(self.teeming);
            $('#enemy_patrol_edit_popup_faction_' + self.id).val(self.faction);

            // Refresh all select pickers so they work again
            refreshSelectPickers();

            let $submitBtn = $('#enemy_patrol_edit_popup_submit_' + self.id);

            $submitBtn.unbind('click');
            $submitBtn.bind('click', function () {
                self.teeming = $('#enemy_patrol_edit_popup_teeming_' + self.id).val();
                self.faction = $('#enemy_patrol_edit_popup_faction_' + self.id).val();

                self.edit();
            });
        };

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        let syncedFn = function (event) {
            // Remove template so our
            let template = Handlebars.templates['map_enemy_patrol_template'];

            let data = $.extend({}, getHandlebarsDefaultVariables(), {
                id: self.id,
                teeming: self.map.options.teeming,
                factions: self.map.options.factions
            });

            let customOptions = {
                'maxWidth': '400',
                'minWidth': '300',
                'className': 'popupCustom'
            };

            // Both the decorator and the layer itself need this popup
            let layers = [
                self.decorator,
                self.layer
            ];

            $.each(layers, function (i, layer) {
                layer.unbindPopup();
                layer.bindPopup(template(data), customOptions);

                // Have you tried turning it off and on again?
                layer.off('popupopen');
                layer.on('popupopen', popupOpenFn);
            });
        };

        this.unregister('synced', this, syncedFn);
        this.register('synced', this, syncedFn);
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, 'this was not an AdminEnemyPatrol', this);
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypatrol/' + self.id,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            success: function (json) {
                self.localDelete();
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    edit() {
        console.assert(this instanceof AdminEnemyPatrol, 'this was not an AdminEnemyPatrol', this);
        this.save();
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, 'this was not an AdminEnemyPatrol', this);
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypatrol',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: getState().getCurrentFloor().id,
                enemy_id: self.enemy_id,
                teeming: self.teeming,
                faction: self.faction,
                color: self.color,
                weight: self.weight,
                vertices: self.getVertices()
            },
            beforeSend: function () {
                $('#enemy_patrol_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.edited,
                    color: c.map.admin.mapobject.colors.editedBorder
                });
            },
            success: function (json) {
                self.map.leafletMap.closePopup();
                self.id = json.id;
                // ID has changed, rebuild the popup
                self.onPopupInit();
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.saved,
                    color: c.map.admin.mapobject.colors.savedBorder
                });
                self.setSynced(true);
            },
            complete: function () {
                $('#enemy_patrol_edit_popup_submit_' + self.id).removeAttr('disabled');
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