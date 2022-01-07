L.Draw.Path = L.Draw.Polyline.extend({
    statics: {
        TYPE: 'path'
    },
    initialize: function (map, options) {
        options.showLength = false;
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.Path.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

// Copy pasted from https://github.com/Leaflet/Leaflet.draw/blob/develop/src/draw/handler/Draw.Polyline.js#L470
// Adjusted so that it uses the correct drawing strings
L.Draw.Path.prototype._getTooltipText = function () {
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

/**
 * @property {Number} linked_awakened_obelisk_id
 */
class Path extends Polyline {
    constructor(map, layer) {
        super(map, layer, {name: 'path'});

        this.label = 'Path';
        this.decorator = null;

        this.setSynced(false);
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof Path, 'this is not a Path', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'linked_awakened_obelisk_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: null
            }),
        ]);
    }

    /**
     *
     * @returns {function}
     * @protected
     */
    _getPolylineColorDefault() {
        return c.map.path.defaultColor;
    }

    /**
     * Rebuild the decorators for this route (directional arrows etc).
     * @private
     */
    _getDecorator() {
        console.assert(this instanceof Path, 'this is not a Path', this);
        return L.polylineDecorator(this.layer, {
            patterns: [
                {
                    offset: 25,
                    repeat: 100,
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 12,
                        pathOptions: {fillOpacity: 1, weight: 0, color: this.polyline.color}
                    })
                }
            ]
        });
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        return this.linked_awakened_obelisk_id === null;
    }

    /**
     * @inheritDoc
     */
    isDeletable() {
        return this.linked_awakened_obelisk_id === null;
    }

    toString() {
        return 'Path';
    }
}
