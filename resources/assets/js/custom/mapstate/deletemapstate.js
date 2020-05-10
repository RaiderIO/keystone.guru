class DeleteMapState extends MapState {
    constructor(map) {
        super(map);
    }


    start() {
        super.start();
        let self = this;

        // Loop through each element to see if they are NOT editable, but ARE deleteable.
        // If so, we have to add them to the 'can delete this' list, and remove them after
        $.each(self.map.mapObjects, function (index, mapObject) {
            if (!mapObject.isEditable() && mapObject.isDeletable()) {
                self.map.editableLayers.addLayer(mapObject.layer);
            }
        });
    }

    stop() {
        super.stop();
        let self = this;

        // Now we make them un-editable again.
        $.each(self.map.mapObjects, function (index, mapObject) {
            if (!mapObject.isEditable() && mapObject.isDeletable()) {
                self.map.editableLayers.removeLayer(mapObject.layer);
            }
        });

        // Fix an issue where it'd remove all layers just because it got removed from the editable layers. Strange.
        self.map.leafletMap.removeLayer(self.map.drawnLayers);
        self.map.leafletMap.addLayer(self.map.drawnLayers);

        // Re-draw the enemies to restore their attributes etc
        let mapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        // All enemies
        for (let index in mapObjectGroup.objects) {
            if (mapObjectGroup.objects.hasOwnProperty(index)) {
                let enemy = mapObjectGroup.objects[index];
                // Refresh
                if( enemy.visual !== null ) {
                    enemy.visual.refresh();
                }
            }
        }
    }
}