class EnemyPack extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Enemy pack';
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        // this.constructor.name.indexOf('EnemyPack') >= 0
        console.assert(this instanceof EnemyPack, this, 'this is not an EnemyPack');
        super.onLayerInit();

        // Show a permanent tooltip for the pack's name
        // this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
    }

    getVertices() {
        let coordinates = this.layer.toGeoJSON().geometry.coordinates[0];
        let result = [];
        for (let i = 0; i < coordinates.length - 1; i++) {
            result.push({x: coordinates[i][0], y: coordinates[i][1]});
        }
        return result;
    }
}