class EnemyPack {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
        this.id = 0;
        this.label = 'Mob pack'
        this.synced = false;
    }

    getContextMenuItems(){
        return [{
            text: this.label,
            disabled: true
        }];
    }

    // To be overridden by any implementing classes
    onLayerInit() {

        // Create the context menu
        this.layer.bindContextMenu({
            contextmenuWidth: 140,
            contextmenuItems: this.getContextMenuItems
        });

        // Show a permanent tooltip for the pack's name
        this.layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
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