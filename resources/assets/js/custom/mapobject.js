class MapObject {

    constructor(map, layer) {
        console.assert(map instanceof DungeonMap, map, 'Passed map is not a DungeonMap!');

        this.synced = false;
        this.map = map;
        this.layer = layer;

        this.id = 0;
        this.label = 'default label';
    }


    _updateContextMenuOptions() {
        return {
            contextmenuWidth: 140,
            // Handled by loop in onLayerInit(), we want to refresh the list on every click
            // contextmenuItems: this.getContextMenuItems()
        };
    }

    getContextMenuItems() {
        return [{
            text: this.label,
            disabled: true
        }];
    }

    /**
     * Sets the colors to use for a map object, if applicable.
     * @param colors object The colors object as found in the constants.js file.
     */
    setColors(colors) {
        this.colors = colors;
    }

    /**
     * Sets the synced state of the map object. Will adjust the colors of the layer if colors are set.
     * @param value bool True to set the status to synced, false to unsynced.
     * @todo Somehow this does not work when trying to set edited colors. Very strange, couldn't get it to work
     */
    setSynced(value) {
        // Only if the colors object was ever set by a parent
        if (typeof this.colors !== 'undefined') {
            // Now synced, was not synced
            if (value && !this.synced) {
                console.log("saved");
                this.layer.setStyle({
                    fillColor: this.colors.saved,
                    color: this.colors.savedBorder
                });
            }
            // No longer synced when it was synced
            else if (!value && this.synced) {
                console.log("edited");
                this.layer.setStyle({
                    fillColor: this.colors.edited,
                    color: this.colors.editedBorder
                });
            }
            // No longer synced, possibly wasn't in the first place, so unsaved
            else if (!value) {
                console.log("unsaved");
                this.layer.setStyle({
                    fillColor: this.colors.unsaved,
                    color: this.colors.unsavedBorder
                });
            }
            this.layer.redraw();
        }

        this.synced = value;
    }

    onLayerInit() {
        let self = this;

        self.layer.bindContextMenu(self._updateContextMenuOptions());
        this.layer.on('contextmenu', function () {
            self.map.leafletMap.contextmenu.removeAllItems();
            let items = self.getContextMenuItems();

            $.each(items, function (index, value) {
                self.map.leafletMap.contextmenu.addItem(value);
            });
            return true;
        });
        this.layer.on('draw:edited', function () {
            console.log('draw:edited');
            // Changed = gone out of sync
            self.setSynced(false);
        });
    }
}