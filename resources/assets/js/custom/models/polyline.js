class Polyline extends MapObject {
    constructor(map, layer, options) {
        super(map, layer, options);
        let self = this;

        this.weight = c.map.polyline.defaultWeight;

        /** Separate layer which represents the animated state of this line, if any */
        this.layerAnimated = null;

        this.setColor(c.map.polyline.defaultColor());

        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Hide it when we're going to edit. It will be visible again when we've synced the polyline
            self._setAnimatedLayerVisibility(!(mapStateChangedEvent.data.newMapState instanceof EditMapState ||
                mapStateChangedEvent.data.newMapState instanceof DeleteMapState));
        });
        this.register('synced', this, function () {
            // Create a separate animated layer if we need to
            if (self.color_animated !== null) {
                // Remove if necessary
                self._setAnimatedLayerVisibility(false);
                self.layerAnimated = L.polyline.antPath(self.getVertices(),
                    $.extend({}, c.map.polyline.polylineOptionsAnimated, {
                        color: self.color,
                        pulseColor: self.color_animated
                    })
                );
                self._assignPopup(self.layerAnimated);
                // Do not set visible to true - this will happen in shown/hidden events
                // self._setAnimatedLayerVisibility(true);
            }
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
                name: 'color',
                type: 'color',
                setter: this.setColor.bind(this),
                default: c.map.polyline.defaultColor
            }),
            new Attribute({
                name: 'color_animated',
                type: 'color',
                edit: getState().hasPaidTier(c.paidtiers.animated_polylines),
                setter: this.setColorAnimated.bind(this),
                default: getState().hasPaidTier(c.paidtiers.animated_polylines) ? c.map.polyline.defaultColorAnimated : null
            }),
            new Attribute({
                name: 'weight',
                type: 'select',
                setter: this.setWeight.bind(this),
                values: weights,
                show_default: false,
                default: c.map.polyline.defaultWeight
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
     * Sets the animated layer to be visible or not.
     * @param visible True to be visible, false to be hidden.
     * @private
     */
    _setAnimatedLayerVisibility(visible) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        // Only if we have an animated layer to begin with
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

    /**
     * @inheritDoc
     */
    localDelete() {
        this._setAnimatedLayerVisibility(false);
        this.layerAnimated = null;

        super.localDelete();
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        return !this.isLocal();
    }

    /**
     * Sets the color for the polyline.
     * @param color
     */
    setColor(color) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        this.color = color;
        this.setColors({
            unsavedBorder: color,
            unsaved: color,

            editedBorder: color,
            edited: color,

            savedBorder: color,
            saved: color
        });
    }

    /**
     * Sets the animated color for this polyline.
     * @param color
     */
    setColorAnimated(color) {
        this.color_animated = color;
    }

    /**
     * Sets the weight for the polyline
     * @param weight
     */
    setWeight(weight) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        this.weight = weight;
        this.layer.setStyle({
            weight: this.weight
        })
    }

    /**
     * To be overridden by any implementing classes.
     */
    onLayerInit() {
        console.assert(this instanceof Polyline, 'this is not a Polyline', this);
        super.onLayerInit();

        // Apply weight to layer
        this.setWeight(this.weight);
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
        this.unregister(['shown', 'hidden'], this);
        this.unregister('synced', this);
    }
}