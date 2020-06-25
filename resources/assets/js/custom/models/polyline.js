class Polyline extends MapObject {
    constructor(map, layer) {
        super(map, layer);
        this.weight = c.map.polyline.defaultWeight;
        this.color = null;

        this.setColor(c.map.polyline.defaultColor());
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
        for(let i = c.map.polyline.minWeight; i <= c.map.polyline.maxWeight; i++ ){
            weights.push({
                id: i,
                name: i
            });
        }

        return $.extend(super._getAttributes(force), {
            floor_id: new Attribute({
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            color: new Attribute({
                type: 'color',
                setter: this.setColor.bind(this),
                default: c.map.polyline.defaultColor
            }),
            color_animated: new Attribute({
                type: 'color',
                setter: this.setColorAnimated.bind(this),
                default: null
            }),
            weight: new Attribute({
                type: 'select',
                setter: this.setWeight.bind(this),
                values: weights,
                show_default: false,
                default: c.map.polyline.defaultWeight
            }),
            vertices: new Attribute({
                type: 'array',
                edit: false,
                getter: function(){
                    return self.getVertices();
                }
            })
        });
    }

    /**
     * @inheritDoc
     */
    isEditable(){
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
}