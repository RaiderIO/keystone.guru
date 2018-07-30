class Route extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.setSynced(true);
    }

    getDifficultyColor(difficulty){
        let palette = window.interpolate(c.map.route.colors);
        // let rand = Math.random();
        let color = palette(difficulty);
        this.setColors({
            saved: color,
            savedBorder: color,
            edited: color,
            editedBorder: color
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof Route, this, 'this is not a Route');
        super.onLayerInit();
        this.layer.setStyle({fillOpacity: 0.6});

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }
}