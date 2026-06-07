L.Draw.Arrow = L.Draw.Polyline.extend({
    statics: {
        TYPE: 'arrow'
    },
    initialize: function (map, options) {
        options.showLength = false;
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.Arrow.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    },
    // Auto-finish after exactly 2 points (start and tip)
    addVertex: function (latlng) {
        L.Draw.Polyline.prototype.addVertex.call(this, latlng);
        if (this._markers.length === 2) {
            this._finishShape();
        }
    }
});

// Copy pasted from path.js pattern
L.Draw.Arrow.prototype._getTooltipText = function () {
    var labelText;
    if (this._markers.length === 0) {
        labelText = {
            text: L.drawLocal.draw.handlers.arrow.tooltip.start
        };
    } else {
        labelText = {
            text: L.drawLocal.draw.handlers.arrow.tooltip.cont
        };
    }
    return labelText;
};

class Arrow extends Polyline {
    constructor(map, layer) {
        super(map, layer, {name: 'arrow', has_route_model_binding: true, ignore_mapping_version_suffix: true});

        this.label = 'Arrow';
        this.decorator = null;

        this.setSynced(false);
    }

    /**
     * @inheritDoc
     */
    _getPolylineColorDefault() {
        return c.map.arrow.defaultColor;
    }

    /**
     * Renders an arrowhead at the tip of the line using a polyline decorator.
     * @returns {*}
     * @protected
     */
    _getDecorator() {
        console.assert(this instanceof Arrow, 'this is not an Arrow', this);
        return L.polylineDecorator(this.layer, {
            patterns: [
                {
                    offset: '100%',
                    repeat: 0,
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 15,
                        pathOptions: {fillOpacity: 1, weight: 0, color: this.polyline.color}
                    })
                }
            ]
        });
    }

    toString() {
        return 'Arrow';
    }
}
