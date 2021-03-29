L.Draw.KillZonePath = L.Draw.Polyline.extend({
    statics: {
        TYPE: 'killzonepath'
    },
    initialize: function (map, options) {
        options.showLength = false;
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.KillZonePath.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

// Copy pasted from https://github.com/Leaflet/Leaflet.draw/blob/develop/src/draw/handler/Draw.Polyline.js#L470
// Adjusted so that it uses the correct drawing strings
L.Draw.KillZonePath.prototype._getTooltipText = function () {
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

class KillZonePath extends Polyline {
    constructor(map, layer) {
        super(map, layer, {name: 'killzonepath'});

        this.label = 'KillZonePath';
        this.decorator = null;

        this.setSynced(false);
    }

    /**
     *
     * @returns {function}
     * @protected
     */
    _getPolylineColorDefault() {
        return c.map.polyline.killzonepath.color;
    }

    /**
     * Rebuild the decorators for this route (directional arrows etc).
     * @private
     */
    _getDecorator() {
        console.assert(this instanceof KillZonePath, 'this is not a KillZonePath', this);
        return L.polylineDecorator(this.layer, {
            patterns: [
                {
                    offset: 25,
                    repeat: 50,
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 24,
                        pathOptions: {fillOpacity: 1, weight: 0, color: this.polyline.color}
                    })
                }
            ]
        });
    }

    toString() {
        return 'KillZonePath';
    }
}