$(function () {
    L.Draw.BrushLine = L.Draw.Polyline.extend({
        statics: {
            TYPE: 'brushline'
        },
        initialize: function (map, options) {
            options.showLength = false;
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.BrushLine.TYPE;
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });

    // Copy pasted from https://github.com/Leaflet/Leaflet.draw/blob/develop/src/draw/handler/Draw.Polyline.js#L470
    // Adjusted so that it uses the correct drawing strings
    L.Draw.BrushLine.prototype._getTooltipText = function () {
		var showLength = this.options.showLength,
			labelText, distanceStr;
		if (this._markers.length === 0) {
			labelText = {
				text: L.drawLocal.draw.handlers.brushline.tooltip.start
			};
		} else {
			distanceStr = showLength ? this._getMeasurementString() : '';

			if (this._markers.length === 1) {
				labelText = {
					text: L.drawLocal.draw.handlers.brushline.tooltip.cont,
					subtext: distanceStr
				};
			} else {
				labelText = {
					text: L.drawLocal.draw.handlers.brushline.tooltip.end,
					subtext: distanceStr
				};
			}
		}
		return labelText;
    }
});

class BrushLine extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        let self = this;

        this.label = 'BrushLine';
        this.type = 'brushline';
        this.weight = c.map.brushline.defaultWeight;
        this.saving = false;
        this.deleting = false;
        this.decorator = null;

        this.setColor(c.map.brushline.defaultColor);
        this.setSynced(false);

        this.register('synced', this, function () {
            self._rebuildDecorator();
        });
        this.register('object:deleted', this, function () {
            self._cleanDecorator();
        });
        this.map.register('map:beforerefresh', this, function () {
            self._cleanDecorator();
        });
    }

    isEditable() {
        return false;
    }

    setColor(color) {
        console.assert(this instanceof BrushLine, this, 'this was not a BrushLine');

        this.brushlineColor = color;
        this.setColors({
            unsavedBorder: color,
            unsaved: color,

            editedBorder: color,
            edited: color,

            savedBorder: color,
            saved: color
        });
    }

    setWeight(weight){
        console.assert(this instanceof BrushLine, this, 'this was not a BrushLine');

        this.weight = weight;
        this.layer.setStyle({
            weight: this.weight
        })
    }

    edit() {
        console.assert(this instanceof BrushLine, this, 'this was not a BrushLine');
        this.save();
    }

    delete() {
        let self = this;
        console.assert(this instanceof BrushLine, this, 'this was not a BrushLine');

        let successFn = function (json) {
            self.signal('object:deleted', {response: json});
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/polyline',
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
        console.assert(this instanceof BrushLine, this, 'this was not a BrushLine');

        let successFn = function (json) {
            self.id = json.id;

            self.setSynced(true);
            self.map.leafletMap.closePopup();
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/polyline',
                dataType: 'json',
                data: {
                    id: self.id,
                    dungeonroute: this.map.getDungeonRoute().publicKey,
                    floor_id: self.map.getCurrentFloor().id,
                    type: self.type,
                    color: self.brushlineColor,
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
            successFn({id: self.id === 0 ? parseInt((Math.random() * 10000000)) : self.id })
        }
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof BrushLine, this, 'this is not an BrushLine');
        super.onLayerInit();

        let self = this;

        // Apply weight to layer
        this.setWeight(this.weight);

        // Only when we're editing
        if (this.map.edit) {
            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well.
            let popupOpenFn = function (event) {
                // Color
                let $color = $('#map_brushline_edit_popup_color_' + self.id);
                $color.val(self.brushlineColor);

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

                self.layer.off('popupopen');
                self.layer.on('popupopen', popupOpenFn);
            });
        }
    }

    getVertices() {
        console.assert(this instanceof BrushLine, this, 'this is not an BrushLine');

        let coordinates = this.layer.toGeoJSON().geometry.coordinates;
        let result = [];
        for (let i = 0; i < coordinates.length; i++) {
            result.push({lat: coordinates[i][0], lng: coordinates[i][1]});
        }
        return result;
    }
}