class Polyline extends MapObject {
    constructor(map, layer) {
        super(map, layer);
        this.weight = c.map.polyline.defaultWeight;

        this.setColor(c.map.polyline.defaultColor());
    }

    /**
     * Sets the color for the polyline.
     * @param color
     */
    setColor(color) {
        console.assert(this instanceof Polyline, 'this was not a Polyline', this);

        this.polylineColor = color;
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
}