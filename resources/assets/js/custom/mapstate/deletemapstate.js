class DeleteMapState extends MapState {
    constructor(map) {
        super(map);
    }

    getName() {
        return 'DeleteMapState';
    }

    start() {
        super.start();
        let self = this;

        // Loop through each element to see if they are NOT editable, but ARE deletable.
        // If they are editable, they are already on the layer and do not need to be added/removed later
        // If so, we have to add them to the 'can delete this' list, and remove them after
        $.each(self.map.mapObjects, function (index, mapObject) {
            if (!mapObject.isEditable() && mapObject.isDeletable() && mapObject.layer !== null) {
                self.map.editableLayers.addLayer(mapObject.layer);
            }
        });
    }

    stop() {
        super.stop();
        let self = this;

        // Now we make them un-editable again (if required)
        $.each(self.map.mapObjects, function (index, mapObject) {
            if (!mapObject.isEditable() && mapObject.isDeletable() && mapObject.layer !== null) {
                self.map.editableLayers.removeLayer(mapObject.layer);
            }
        });

        // Fix an issue where it'd remove all layers just because it got removed from the editable layers. Strange.
        self.map.leafletMap.removeLayer(self.map.drawnLayers);
        self.map.leafletMap.addLayer(self.map.drawnLayers);
    }
}