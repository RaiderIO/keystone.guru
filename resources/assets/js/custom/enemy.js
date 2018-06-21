class Enemy extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Enemy';
        let palette = window.interpolate(c.map.enemy.colors);
        let rand = Math.random();
        console.log(rand);
        let color = palette(rand);
        // let hex = "#" + color.values[0].toString(16) + color.values[1].toString(16) + color.values[2].toString(16);

        layer.setStyle({fillOpacity: 0.6});
        this.setColors({
            saved: color,
            savedBorder: color,
            edited: color,
            editedBorder: color
        });
        this.setSynced(true);
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        super.onLayerInit();
        let self = this;

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }
}