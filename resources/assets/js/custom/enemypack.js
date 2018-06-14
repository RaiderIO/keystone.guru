class EnemyPack {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
        this.id = 0;
        this.label = 'Mob pack'
    }

    // To be overridden by any implementing classes
    onLayerInit() {

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