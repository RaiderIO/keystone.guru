class MapObject extends Signalable {

    /**
     *
     * @param map
     * @param layer {L.layer}
     */
    constructor(map, layer) {
        super();
        console.assert(map instanceof DungeonMap, map, 'Passed map is not a DungeonMap!');
        let self = this;

        this.synced = false;
        this.map = map;
        this.layer = layer;

        this.id = 0;
        this.label = 'default label';
        this.decorator = null;

        this.register('synced', this, function () {
            self._rebuildDecorator();
        });
        this.register('object:deleted', this, function () {
            self._cleanDecorator();
        });
        this.map.register('map:beforerefresh', this, function () {
            self._cleanDecorator();
        });

        this.register(['shown', 'hidden'], this, function (event) {
            if (event.data.visible) {
                self._rebuildDecorator();
            } else {
                self._cleanDecorator();
            }
        });
    }

    /**
     * Cleans up the decorator of this route, removing it from the map.
     * @private
     */
    _cleanDecorator() {
        console.assert(this instanceof MapObject, this, 'this is not a MapObject');

        if (this.decorator !== null) {
            this.map.leafletMap.removeLayer(this.decorator);
        }
    }

    /**
     * Rebuild the decorators for this route (directional arrows etc).
     * @private
     */
    _rebuildDecorator() {
        console.assert(this instanceof MapObject, this, 'this is not an MapObject');

        this._cleanDecorator();

        this.decorator = this._getDecorator();
        // Only if set after the getter finished
        if (this.decorator !== null) {
            this.decorator.addTo(this.map.leafletMap);
        }
    }

    _getDecorator() {
        return null;
    }

    _updateContextMenuOptions() {
        return {
            contextmenuWidth: 140,
            // Handled by loop in onLayerInit(), we want to refresh the list on every click
            // contextmenuItems: this.getContextMenuItems()
        };
    }

    getContextMenuItems() {
        return [
            //     {
            //     text: this.label + ' (synced: ' + this.synced + ')',
            //     disabled: true
            // }
        ];
    }

    /**
     * Gets if this map object is editable, default is true. May be overridden.
     * @returns {boolean}
     */
    isEditable() {
        return true;
    }

    /**
     * Applies the tooltip to this map object if applicable.
     */
    bindTooltip() {

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
        console.assert(this instanceof MapObject, this, 'this is not a MapObject');

        // Only if the colors object was ever set by a parent
        if (typeof this.colors !== 'undefined' && typeof this.layer.setStyle === 'function') {
            // Now synced
            if (value) {
                this.layer.setStyle({
                    fillColor: this.colors.saved,
                    color: this.colors.savedBorder
                });
            }
            // No longer synced when it was synced
            else if (!value && this.synced) {
                this.layer.setStyle({
                    fillColor: this.colors.edited,
                    color: this.colors.editedBorder
                });
            }
            // No longer synced, possibly wasn't in the first place, so unsaved
            else if (!value) {
                this.layer.setStyle({
                    fillColor: this.colors.unsaved,
                    color: this.colors.unsavedBorder
                });
            }
            this.layer.redraw();
        }

        // If we're synced, trigger the synced event
        if (value) {
            // Refresh the tooltip
            this.bindTooltip();

            this.signal('synced');
        }

        this.synced = value;
    }

    onLayerInit() {
        let self = this;
        console.assert(this instanceof MapObject, this, 'this is not a MapObject');

        self.layer.bindContextMenu(self._updateContextMenuOptions());
        self.layer.on('contextmenu', function () {
            let items = self.getContextMenuItems();
            self.map.leafletMap.contextmenu.removeAllItems();

            $.each(items, function (index, value) {
                self.map.leafletMap.contextmenu.addItem(value);
            });
            return true;
        });
        self.layer.on('draw:edited', function () {
            // Changed = gone out of sync
            self.setSynced(false);
        });
    }

    cleanup() {
        this._cleanupSignals();
    }
}