class EnemyPack {
    constructor(map, layer) {
        console.assert(map.constructor.name.indexOf('DungeonMap') >= 0, map, 'Passed map is not a DungeonMap!');

        this.map = map;
        this.layer = layer;
        this.id = 0;
        this.label = 'Mob pack';
        this.synced = false;
    }

    getContextMenuItems(){
        return [{
            text: this.label,
            disabled: true
        }];
    }

    _updateContextMenuOptions(){
        return {
            contextmenuWidth: 140,
            // Handled by loop in onLayerInit(), we want to refresh the list on every click
            // contextmenuItems: this.getContextMenuItems()
        };
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this.constructor.name.indexOf('EnemyPack') >= 0, this, 'this is not an EnemyPack');

        let self = this;

        // Bind it now
        console.log(self, self.layer);

        self.layer.bindContextMenu(self._updateContextMenuOptions());
        this.layer.on('contextmenu', function(){
            self.map.leafletMap.contextmenu.removeAllItems();
            let items = self.getContextMenuItems();

            $.each(items, function (index, value) {
                self.map.leafletMap.contextmenu.addItem(value);
            });
            return true;
        });

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