// $(function () {
L.Draw.MountableArea = L.Draw.Polygon.extend({
    statics: {
        TYPE: 'mountablearea'
    },
    options: {},
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.MountableArea.TYPE;

        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

// });

/**
 * @property {Number} floor_id
 * @property {Number|null} speed
 * @property {Object} polyline
 */
class MountableArea extends HullPolyline {
    constructor(map, layer) {
        super(map, layer, {name: 'mountablearea', has_route_model_binding: true});

        this.group = null;
        this.label = 'Mountable Area';
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof MountableArea, 'this was not a MountableArea', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'speed',
                type: 'int',
                edit: true,
                default: null
            })
        ]);
    }

    /**
     *
     * @returns {string}
     * @protected
     */
    _getPolylineColorDefault() {
        return c.map.mountablearea.color;
    }

    /**
     * @inheritDoc
     */
    _getPolylineWeightDefault() {
        return c.map.mountablearea.polygonOptions.weight;
    }

    /**
     * Mountable areas are always drawn in the same colour.
     * @inheritDoc
     */
    _isColorEditable() {
        return false;
    }

    /**
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // The nested polyline is loaded through this same method; build the hull once, for the area itself
        if (parentAttribute === null && !(getState().getMapContext() instanceof MapContextMappingVersionEdit)) {
            this._updateHullLayer();
        }
    }

    isEditableByPopup() {
        return false;
    }

    /**
     * The area's own vertices.
     * @inheritDoc
     */
    _getHullPoints() {
        console.assert(this instanceof MountableArea, 'this is not a MountableArea', this);

        return this.getVertices().map(latLng => [latLng.lat, latLng.lng]);
    }

    /**
     * @inheritDoc
     */
    _getHullMargin() {
        return c.map.mountablearea.margin;
    }

    /**
     * @inheritDoc
     */
    _getHullArcSegments() {
        return c.map.mountablearea.arcSegments;
    }

    /**
     * @inheritDoc
     */
    _getHullPolygonOptions() {
        return c.map.mountablearea.polygonOptions;
    }

    /**
     * @inheritDoc
     */
    _getHullMapObjectGroup() {
        return this.map.mapObjectGroupManager.getMountableAreaMapObjectGroup();
    }

    bindTooltip() {
        super.bindTooltip();

        if (this.layer !== null) {
            let displayText = lang.get('js.mountablearea_tooltip_label', {speed: this.speed ?? MOVEMENT_SPEED_MOUNTED});

            this.layer.bindTooltip(displayText.trim(), {
                sticky: true,
                direction: 'top'
            });
        }
    }

    toString() {
        console.assert(this instanceof MountableArea, 'this is not a MountableArea', this);

        return 'Mountable area-' + this.id;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {MountableArea};
}
