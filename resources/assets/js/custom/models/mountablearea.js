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

class MountableArea extends VersionableMapObject {
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

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            new Attribute({
                name: 'speed',
                type: 'int',
                edit: true,
                default: null
            }),
            new Attribute({
                name: 'vertices',
                type: 'array',
                edit: false,
                getter: function () {
                    return self.getVertices();
                }
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
     * Sets the color of the pack.
     * @param color
     */
    setColor(color) {
        console.assert(this instanceof MountableArea, 'this was not a MountableArea', this);

        this.color = color;
        this.layer.setStyle({
            fillColor: this.color ?? this._getPolylineColorDefault(),
            color: this.color ?? this._getPolylineColorDefault()
        });
        this.layer.redraw();
    }

    /**
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // Only called when not in admin state
        if (!(getState().getMapContext() instanceof MapContextMappingVersionEdit)) {
            this._updateHullLayer();
        }
    }

    isEditableByPopup() {
        return false;
    }

    /**
     * Creates a new layer ready to be assigned somewhere.
     * @returns {L.Layer|null}
     */
    _updateHullLayer() {
        console.assert(this instanceof MountableArea, 'this is not a MountableArea', this);

        let result = null;
        let latLngs = this.getVertices();

        // Build a layer based off a hull if we're supposed to
        if (latLngs.length > 1) {
            let vertices = [];
            for (let i = 0; i < latLngs.length; i++) {
                vertices.push([latLngs[i].lat, latLngs[i].lng]);
            }

            let hullPoints = hull(vertices, 100);
            // Only if we can actually make an offset
            if (hullPoints.length > 1) {
                try {
                    let offsetLatLngs = createOffsetPolygon(
                        hullPoints.map(point => ({lat: point[0], lng: point[1]})),
                        c.map.mountablearea.margin,
                        c.map.mountablearea.arcSegments(hullPoints.length)
                    );

                    result = L.polygon([offsetLatLngs], c.map.mountablearea.polygonOptions);
                } catch (error) {
                    // Not particularly interesting to spam the console with
                    console.error('Unable to create offset for mountable area', this.id, error);
                }
            }
        }

        let mountableAreaMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MOUNTABLE_AREA);
        mountableAreaMapObjectGroup.setLayerToMapObject(result, this);
        this.rebindTooltip();
    }

    /**
     *
     * @returns {[]}
     */
    getVertices() {
        console.assert(this instanceof MountableArea, 'this is not a MountableArea', this);

        let coordinates = this.layer.toGeoJSON().geometry.coordinates[0];
        let result = [];
        for (let i = 0; i < coordinates.length - 1; i++) {
            result.push({lat: coordinates[i][1], lng: coordinates[i][0]});
        }
        return result;
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
