$(function () {
    L.Draw.Route = L.Draw.Polyline.extend({
        statics: {
            TYPE: 'route'
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.Route.TYPE;
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });

    // Copy pasted from https://github.com/Leaflet/Leaflet.draw/blob/develop/src/draw/handler/Draw.Polyline.js#L470
    // Adjusted so that it uses the correct drawing strings
    L.Draw.Route.prototype._getTooltipText = function () {
		var showLength = this.options.showLength,
			labelText, distanceStr;
		if (this._markers.length === 0) {
			labelText = {
				text: L.drawLocal.draw.handlers.route.tooltip.start
			};
		} else {
			distanceStr = showLength ? this._getMeasurementString() : '';

			if (this._markers.length === 1) {
				labelText = {
					text: L.drawLocal.draw.handlers.route.tooltip.cont,
					subtext: distanceStr
				};
			} else {
				labelText = {
					text: L.drawLocal.draw.handlers.route.tooltip.end,
					subtext: distanceStr
				};
			}
		}
		return labelText;
    }
});

class Route extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        let self = this;

        this.label = 'Route';
        this.saving = false;
        this.deleting = false;
        this.decorator = null;

        this.setColor(c.map.route.defaultColor);
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

    /**
     * Cleans up the decorator of this route, removing it from the map.
     * @private
     */
    _cleanDecorator() {
        console.assert(this instanceof Route, this, 'this is not an Route');

        if (this.decorator !== null) {
            this.map.leafletMap.removeLayer(this.decorator);
        }
    }

    /**
     * Rebuild the decorators for this route (directional arrows etc).
     * @private
     */
    _rebuildDecorator() {
        console.assert(this instanceof Route, this, 'this is not an Route');

        this._cleanDecorator();

        this.decorator = L.polylineDecorator(this.layer, {
            patterns: [
                {
                    offset: 25,
                    repeat: 100,
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 12,
                        pathOptions: {fillOpacity: 1, weight: 0, color: this.routeColor}
                    })
                }
            ]
        });
        this.decorator.addTo(this.map.leafletMap);
    }

    setColor(color) {
        this.routeColor = color;
        this.setColors({
            unsavedBorder: color,
            unsaved: color,

            editedBorder: color,
            edited: color,

            savedBorder: color,
            saved: color
        });
    }

    edit() {
        console.assert(this instanceof Route, this, 'this was not a Route');
        this.save();
    }

    delete() {
        let self = this;
        console.assert(this instanceof Route, this, 'this was not a Route');

        let successFn = function (json) {
            self.signal('object:deleted', {response: json});
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/route',
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
        console.assert(this instanceof Route, this, 'this was not a Route');

        let successFn = function (json) {
            self.id = json.id;

            self.setSynced(true);
            self.map.leafletMap.closePopup();
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/route',
                dataType: 'json',
                data: {
                    id: self.id,
                    dungeonroute: this.map.getDungeonRoute().publicKey,
                    floor_id: self.map.getCurrentFloor().id,
                    color: self.routeColor,
                    vertices: self.getVertices(),
                },
                beforeSend: function () {
                    self.saving = true;
                    $('#map_route_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
                },
                success: successFn,
                complete: function () {
                    $('#map_route_edit_popup_submit_' + self.id).removeAttr('disabled');
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
        console.assert(this instanceof Route, this, 'this is not an Route');
        super.onLayerInit();

        let self = this;

        // Only when we're editing
        if (this.map.edit) {
            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well.
            let popupOpenFn = function (event) {
                let $color = $('#map_route_edit_popup_color_' + self.id);
                $color.val(self.routeColor);

                // Class color buttons
                let $classColors = $('.map_route_edit_popup_class_color');
                $classColors.unbind('click');
                $classColors.bind('click', function () {
                    $color.val($(this).data('color'));
                });

                // Prevent multiple binds to click
                let $submitBtn = $('#map_route_edit_popup_submit_' + self.id);
                $submitBtn.unbind('click');
                $submitBtn.bind('click', function () {
                    self.setColor($('#map_route_edit_popup_color_' + self.id).val());

                    self.edit();
                });
            };

            // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
            self.register('synced', this, function (event) {
                let customPopupHtml = $('#map_route_edit_popup_template').html();
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
        console.assert(this instanceof Route, this, 'this is not an Route');

        let coordinates = this.layer.toGeoJSON().geometry.coordinates;
        let result = [];
        for (let i = 0; i < coordinates.length; i++) {
            result.push({lat: coordinates[i][0], lng: coordinates[i][1]});
        }
        return result;
    }
}