class EnemyPack {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
        this.id = 0;
        this.label = 'Mob pack';
        this.synced = false;

        this.contextMenuOptions = {};
    }

    getContextMenuItems(){
        return [{
            text: this.label += '1',
            disabled: true
        }];
    }

    _updateContextMenuOptions(){
        console.log("Updated context menu!");
        return this.contextMenuOptions = {
            contextmenuWidth: 140,
            contextmenuItems: this.getContextMenuItems()
        };
    }

    _onContextMenu(event){
        console.log(event);
        // Hack to get the context menu to refresh
        let self = this;
        // Remove ourselves
        self.layer.on('contextmenu', function(){});
        // Bind the context menu with current options
        self.layer.bindContextMenu(self._updateContextMenuOptions());
        // Show it
        self.layer.fire('contextmenu');
        // Unbind what was bound
        self.layer.unbindContextMenu();
        // Reattach ourselves like the parasite we are
        self.layer.on('contextmenu', self._onContextMenu);
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        let self = this;
        // Refresh the context menu
        // this.layer.on('contextmenu', this._onContextMenu);
        this.layer.on('contextmenu', function(){
            self.layer.bindContextMenu(self._updateContextMenuOptions());
            return true;
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