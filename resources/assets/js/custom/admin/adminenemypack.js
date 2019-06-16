class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
        this.setSynced(false);
    }

    onLayerInit() {
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
        super.onLayerInit();
        this.onPopupInit();
    }

    onPopupInit(){
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
        let self = this;

        // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
        // This also cannot be a private function since that'll apparently give different signatures as well.
        let popupOpenFn = function (event) {
            $('#enemy_pack_edit_popup_teeming_' + self.id).val(self.teeming);
            $('#enemy_pack_edit_popup_faction_' + self.id).val(self.faction);

            // Refresh all select pickers so they work again
            refreshSelectPickers();

            let $submitBtn = $('#enemy_pack_edit_popup_submit_' + self.id);

            $submitBtn.unbind('click');
            $submitBtn.bind('click', function () {
                self.teeming = $('#enemy_pack_edit_popup_teeming_' + self.id).val();
                self.faction = $('#enemy_pack_edit_popup_faction_' + self.id).val();

                self.edit();
            });
        };

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        let syncedFn = function (event) {
            // Remove template so our
            let template = Handlebars.templates['map_enemy_pack_template'];

            let data = $.extend({
                id: self.id,
                teeming: self.map.options.teeming,
                factions: self.map.options.factions
            }, getHandlebarsDefaultVariables());

            let customOptions = {
                'maxWidth': '400',
                'minWidth': '300',
                'className': 'popupCustom'
            };

            self.layer.unbindPopup();
            self.layer.bindPopup(template(data), customOptions);

            // Have you tried turning it off and on again?
            self.layer.off('popupopen');
            self.layer.on('popupopen', popupOpenFn);
        };

        this.unregister('synced', this, syncedFn);
        this.register('synced', this, syncedFn);

        self.map.leafletMap.on('contextmenu', function () {
            if (self.currentPatrolPolyline !== null) {
                self.map.leafletMap.addLayer(self.currentPatrolPolyline);
                self.currentPatrolPolyline.disable();
            }
        });
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypack/' + self.id,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            beforeSend: function () {
                self.deleting = true;
            },
            success: function (json) {
                self.localDelete();
            },
            complete: function () {
                self.deleting = false;
            },
            error: function () {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                self.setSynced(false);
            }
        });
    }

    edit() {
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
        this.save();
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminEnemyPack, this, 'this was not an AdminEnemyPack');
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypack',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: self.map.getCurrentFloor().id,
                label: self.label,
                teeming: self.teeming,
                faction: self.faction,
                vertices: self.getVertices()
            },
            beforeSend: function () {
                $('#enemy_pack_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
                self.saving = true;
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
                $('#enemy_pack_edit_popup_submit_' + self.id).removeAttr('disabled');
                self.saving = false;
            },
            error: function () {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);
            }
        });
    }
}