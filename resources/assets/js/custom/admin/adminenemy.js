class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.npc_id = 0;
        // Init to an empty value
        this.enemy_pack_id = -1;
        this.teeming = '';
        // Filled when we're currently drawing a patrol line
        this.currentPatrolPolyline = null;

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
    }

    onLayerInit(){
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        super.onLayerInit();

        let self = this;
        self.map.leafletMap.on('contextmenu', function(){
            if( self.currentPatrolPolyline !== null ){
                self.map.leafletMap.addLayer(self.currentPatrolPolyline);
                self.currentPatrolPolyline.disable();
            }
        });
    }

    onPopupInit(){
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        let self = this;

        // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
        // This also cannot be a private function since that'll apparently give different signatures as well.
        let popupOpenFn = function (event) {
            $('#enemy_edit_popup_teeming_' + self.id).val(self.teeming);
            $('#enemy_edit_popup_faction_' + self.id).val(self.faction);
            $('#enemy_edit_popup_enemy_forces_override_' + self.id).val(self.enemy_forces_override);
            $('#enemy_edit_popup_npc_' + self.id).val(self.npc_id);

            // Refresh all select pickers so they work again
            let $selectpicker = $('.selectpicker');
            $selectpicker.selectpicker('refresh');
            $selectpicker.selectpicker('render');

            let $submitBtn = $('#enemy_edit_popup_submit_' + self.id);

            $submitBtn.unbind('click');
            $submitBtn.bind('click', function () {
                self.teeming = $('#enemy_edit_popup_teeming_' + self.id).val();
                self.faction = $('#enemy_edit_popup_faction_' + self.id).val();
                self.enemy_forces_override = $('#enemy_edit_popup_enemy_forces_override_' + self.id).val();
                self.npc_id = $('#enemy_edit_popup_npc_' + self.id).val();

                self.edit();
            });
        };

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, function(event){
            let customPopupHtml = $('#enemy_edit_popup_template').html();
            // Remove template so our
            let template = handlebars.compile(customPopupHtml);

            let data = {id: self.id};

            // Build the status bar from the template
            customPopupHtml = template(data);

            let customOptions = {
                'maxWidth': '400',
                'minWidth': '300',
                'className': 'popupCustom'
            };

            self.layer.unbindPopup();
            self.layer.bindPopup(customPopupHtml, customOptions);

            // Have you tried turning it off and on again?
            self.layer.off('popupopen', popupOpenFn);
            self.layer.on('popupopen', popupOpenFn);
        });
    }

    edit() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        this.save();
    }

    delete() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        let self = this;
        $.ajax({
            type: 'POST',
            url: '/ajax/enemy',
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
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        let self = this;

        $.ajax({
            type: 'POST',
            url: '/ajax/enemy',
            dataType: 'json',
            data: {
                id: self.id,
                enemy_pack_id: self.enemy_pack_id,
                npc_id: self.npc_id,
                floor_id: self.map.getCurrentFloor().id,
                teeming: self.teeming,
                faction: self.faction,
                enemy_forces_override: self.enemy_forces_override,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng
            },
            beforeSend: function () {
                self.editing = true;
                $('#enemy_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
            },
            success: function (json) {
                self.setSynced(true);
                self.map.leafletMap.closePopup();
                // May be null if not set at all (yet)
                if (json.hasOwnProperty('npc') && json.npc !== null) {
                    self.setNpc(json.npc);
                }
            },
            complete: function () {
                $('#enemy_edit_popup_submit_' + self.id).removeAttr('disabled');
                self.editing = false;
            },
            error: function () {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);
            }
        });
    }
}