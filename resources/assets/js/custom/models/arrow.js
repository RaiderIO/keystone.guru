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
    onLayerInit() {
        console.assert(this instanceof Arrow, 'this is not an Arrow', this);
        super.onLayerInit();

        // An arrow is always exactly two vertices, so it opts out of the middle markers that
        // Leaflet.draw uses to insert new ones. MapObjectGroup#setLayerToMapObject() hands the map
        // object a brand new layer on every rebuild (floor switch, live session update), so this
        // must be (re)applied per layer rather than once in the constructor (#3966).
        this.layer.options.allowVertexCreationDuringEdit = false;
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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Arrow;
}
