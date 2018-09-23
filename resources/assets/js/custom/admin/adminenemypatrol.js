class AdminEnemyPatrol extends EnemyPatrol {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
        this.setSynced(false);

        this.enemy_id = -1;
    }

    onLayerInit(){
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');
        super.onLayerInit();

        this.onPopupInit();
    }

    onPopupInit(){
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');

        // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
        // This also cannot be a private function since that'll apparently give different signatures as well.
        let popupOpenFn = function (event) {
            console.log(self.faction);
            $('#enemy_patrol_edit_popup_faction_' + self.id).val(self.faction);

            // Refresh all select pickers so they work again
            let $selectpicker = $('.selectpicker');
            $selectpicker.selectpicker('refresh');
            $selectpicker.selectpicker('render');

            let $submitBtn = $('#enemy_patrol_edit_popup_submit_' + self.id);

            $submitBtn.unbind('click');
            $submitBtn.bind('click', function () {
                self.faction = $('#enemy_patrol_edit_popup_faction_' + self.id).val();

                self.edit();
            });
        };

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.unregister('synced', this);
        this.register('synced', this, function(event){
            let customPopupHtml = $('#enemy_patrol_edit_popup_template').html();
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

            // Both the decorator and the layer itself need this popup
            let layers = [
                self.decorator,
                self.layer
            ];
            console.log(layers);
            $.each(layers, function(i, layer){
                console.log(i, layer);
                layer.unbindPopup();
                layer.bindPopup(customPopupHtml, customOptions);

                // Have you tried turning it off and on again?
                layer.off('popupopen', popupOpenFn);
                layer.on('popupopen', popupOpenFn);
            });
        });
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypatrol',
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
                self.setSynced(false);
            }
        });
    }

    edit(){
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');
        this.save();
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypatrol',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: self.map.getCurrentFloor().id,
                enemy_id: self.enemy_id,
                faction: self.faction,
                vertices: self.getVertices()
            },
            beforeSend: function () {
                $('#enemy_patrol_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
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
                $('#enemy_patrol_edit_popup_submit_' + self.id).removeAttr('disabled');
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