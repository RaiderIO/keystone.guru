class Polyline extends MapObject {
    constructor(map, layer, options) {
        super(map, layer, options);
        let self = this;

        this.weight = c.map.polyline.defaultWeight;

        /** Separate layer which represents the animated state of this line, if any */
        this.layerAnimated = null;

        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Don't interfere with refreshing map states; that's what the map:beforerefresh event is for
            if (mapStateChangedEvent.data.previousMapState instanceof EditMapState ||
                mapStateChangedEvent.data.previousMapState instanceof DeleteMapState) {
                // Show it again when the edit/delete map state was restored
                self._setAnimatedLayerVisibility(true);
            }
            // Don't do else; we may transition from edit to delete map state
            if (mapStateChangedEvent.data.newMapState instanceof EditMapState ||
                mapStateChangedEvent.data.newMapState instanceof DeleteMapState) {
                // Hide it when we're going to edit. It will be visible again when we've synced the polyline
                self._setAnimatedLayerVisibility(false);
            }
        });
        // Hide yo wife, hide yo children (and animated layers)
        this.map.register(['map:beforerefresh'], this, function (mapBeforeRefreshEvent) {
            self._setAnimatedLayerVisibility(false);
        });
        this.register(['shown', 'hidden'], this, function (shownHiddenEvent) {
            self._setAnimatedLayerVisibility(shownHiddenEvent.data.visible);
        });
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        let weights = [];
        for (let i = c.map.polyline.minWeight; i <= c.map.polyline.maxWeight; i++) {
            weights.push({
                id: i,
                name: i
            });
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            new Attribute({
                name: 'polyline',
                type: 'object',
                default: null,
                setter: this.setPolyline.bind(this),
                attributes: [
                    new Attribute({
                        name: 'color',
                        type: 'color',
                        setter: this.setPolylineColor.bind(this),
                        default: this._getPolylineColorDefault.bind(this)
                    }),
                    new Attribute({
                        name: 'color_animated',
                        type: 'color',
                        edit: getState().hasPaidTier(c.paidtiers.animated_polylines),
                        setter: this.setPolylineColorAnimated.bind(this),
                        // This default sets enemy patrols to animate by default - do not want?
                        default: function () {
                            let result = null;

                            if (self.id === null && getState().hasPaidTier(c.paidtiers.animated_polylines)) {
                                result = c.map.polyline.defaultColorAnimated;
                            }

                            return result;
                        }
                    }),
                    new Attribute({
                        name: 'weight',
                        type: 'select',
                        setter: this.setPolylineWeight.bind(this),
                        values: weights,
                        show_default: false,
                        default: c.map.polyline.defaultWeight
                    }),
                    new Attribute({
                        name: 'vertices_json',
                        type: 'string',
                        edit: false,
                        getter: function () {
                            return JSON.stringify(self.getVertices());
                        }
                    })
                ]
            }),
        ]);
    }

    /**
     *
     * @returns {function}
     * @protected
     */
    _getPolylineColorDefault() {
        return c.map.polyline.defaultColor;
    }

    /**
     * Sets the animated layer to be visible or not.
     * @param visible True to be visible, false to be hidden.
     * @private
     */
    _setAnimatedLayerVisibility(visible) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        if (this.layerAnimated !== null) {
            if (visible) {
                if (this.map.drawnLayers.hasLayer(this.layer)) {
                    this.map.drawnLayers.removeLayer(this.layer);
                }
                if (!this.map.drawnLayers.hasLayer(this.layerAnimated)) {
                    this.map.drawnLayers.addLayer(this.layerAnimated);
                }
            } else {
                if (!this.map.drawnLayers.hasLayer(this.layer)) {
                    this.map.drawnLayers.addLayer(this.layer);
                }
                if (this.map.drawnLayers.hasLayer(this.layerAnimated)) {
                    this.map.drawnLayers.removeLayer(this.layerAnimated);
                }
            }
        }
    }

    setPolyline(polyline) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);
        this.polyline = polyline;
    }

    /**
     * @inheritDoc
     */
    localDelete() {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        this._setAnimatedLayerVisibility(false);
        this.layerAnimated = null;

        super.localDelete();
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        return !this.isLocal();
    }

    /**
     * Sets the color for the polyline.
     * @param color
     */
    setPolylineColor(color) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        this.polyline.color = color;
        this.layer.setStyle({
            fillColor: this.polyline.color,
            color: this.polyline.color
        });
        this.layer.redraw();
    }

    /**
     * Sets the animated color for this polyline.
     * @param color
     */
    setPolylineColorAnimated(color) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        this.polyline.color_animated = color;

        // Remove if necessary
        this._setAnimatedLayerVisibility(false);
        this.layerAnimated = null;

        if (this.polyline.color_animated !== null) {
            this.layerAnimated = L.polyline.antPath(this.getVertices(),
                $.extend({}, c.map.polyline.polylineOptionsAnimated, {
                    color: this.polyline.color,
                    pulseColor: this.polyline.color_animated,
                    weight: this.polyline.weight
                })
            );
            this._assignPopup(this.layerAnimated);
            this._setAnimatedLayerVisibility(true);
        }
    }

    /**
     * Sets the weight for the polyline
     * @param weight
     */
    setPolylineWeight(weight) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        this.polyline.weight = weight;
        this.layer.setStyle({
            weight: this.polyline.weight
        });

        if (typeof this.layerAnimated !== 'undefined' && this.layerAnimated !== null) {
            this.layerAnimated.setStyle({
                weight: this.polyline.weight
            });
        }
    }

    /**
     * Gets the vertices of this polyline.
     * @returns {Array}
     */
    getVertices() {
        console.assert(this instanceof Polyline, 'this is not a Polyline', this);

        let coordinates = this.layer.toGeoJSON().geometry.coordinates;
        let result = [];
        for (let i = 0; i < coordinates.length; i++) {
            // 0 is lng, 1 is lat
            result.push({lat: coordinates[i][1], lng: coordinates[i][0]});
        }
        return result;
    }

    cleanup() {
        super.cleanup();
        // Remove the animated layer if there was any
        this._setAnimatedLayerVisibility(false);
        this.map.unregister('map:mapstatechanged', this);
        this.map.unregister('map:beforerefresh', this);
        this.unregister(['shown', 'hidden'], this);
    }
}