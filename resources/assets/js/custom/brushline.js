$(function () {
    L.Draw.Brushline = L.Draw.Polyline.extend({
        statics: {
            TYPE: 'brushline'
        },
        initialize: function (map, options) {
            options.showLength = false;
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.Brushline.TYPE;
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

class Brushline extends Polyline {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Brushline';
        this.type = 'brushline';
        this.saving = false;
        this.deleting = false;
        this.decorator = null;

        this.setSynced(false);
    }

    isEditable() {
        return true;
    }

    edit() {
        console.assert(this instanceof Brushline, this, 'this was not a Brushline');
        this.save();
    }

    delete() {
        let self = this;
        console.assert(this instanceof Brushline, this, 'this was not a Brushline');

        let successFn = function (json) {
            self.signal('object:deleted', {response: json});
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/brushline',
                dataType: 'json',
                data: {
                    _method: 'DELETE',
                    id: self.id
                },
                beforeSend: function () {
                    self.deleting = true;
                },
                success: successFn,
                complete: function () {
                    self.deleting = false;
                },
                error: function () {
                    self.setSynced(false);
                }
            });
        } else {
            successFn();
        }
    }

    save() {
        let self = this;
        console.assert(this instanceof Brushline, this, 'this was not a Brushline');

        let successFn = function (json) {
            self.id = json.id;

            self.setSynced(true);
            self.map.leafletMap.closePopup();
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/brushline',
                dataType: 'json',
                data: {
                    id: self.id,
                    dungeonroute: this.map.getDungeonRoute().publicKey,
                    floor_id: self.map.getCurrentFloor().id,
                    color: self.polylineColor,
                    weight: self.weight,
                    vertices: self.getVertices(),
                },
                beforeSend: function () {
                    self.saving = true;
                    $('#map_brushline_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
                },
                success: successFn,
                complete: function () {
                    $('#map_brushline_edit_popup_submit_' + self.id).removeAttr('disabled');
                    self.saving = false;
                },
                error: function () {
                    // Even if we were synced, make sure user knows it's no longer / an error occurred
                    self.setSynced(false);
                }
            });
        } else {
            // We have to supply an ID to keep everything working properly
            successFn({id: self.id === 0 ? parseInt((Math.random() * 10000000)) : self.id})
        }
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof Brushline, this, 'this is not an Brushline');
        super.onLayerInit();

        // Only when we're editing
        if (this.map.edit) {
            let self = this;

            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well.
            let popupOpenFn = function (event) {
                // Color
                let $color = $('#map_brushline_edit_popup_color_' + self.id);
                $color.val(self.polylineColor);

                // Class color buttons
                let $classColors = $('.map_polyline_edit_popup_class_color');
                $classColors.unbind('click');
                $classColors.bind('click', function () {
                    $color.val($(this).data('color'));
                });

                // Weight
                let $weight = $('#map_brushline_edit_popup_weight_' + self.id);
                // Convert weight to index
                $weight.val(self.weight);

                // Refresh all select pickers so they work again
                refreshSelectPickers();

                // Prevent multiple binds to click
                let $submitBtn = $('#map_brushline_edit_popup_submit_' + self.id);
                $submitBtn.unbind('click');
                $submitBtn.bind('click', function () {
                    self.setColor($('#map_brushline_edit_popup_color_' + self.id).val());
                    self.setWeight($('#map_brushline_edit_popup_weight_' + self.id).val());

                    self.edit();
                });
            };

            // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
            self.register('synced', this, function (event) {
                let customPopupHtml = $('#map_brushline_edit_popup_template').html();
                // Remove template so our
                let template = Handlebars.compile(customPopupHtml);

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

                self.layer.off('popupopen');
                self.layer.on('popupopen', popupOpenFn);
            });
        }
    }
}