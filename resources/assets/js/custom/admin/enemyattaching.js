class EnemyAttaching {
    constructor(map) {
        console.assert(map instanceof DungeonMap);
        let self = this;

        this.drawingEnemy = false;
        this.drawingEnemyPack = false;
        this.currentEnemyPackVertices = [];

        this.map = map;
        this.map.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
            // Drawing an enemy
            if (e.layerType === 'enemy') {
                self.drawingEnemy = true;
            } else if (e.layerType === 'enemypack') {
                self.drawingEnemyPack = true;
            }
        });

        // Reset
        this.map.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
            self.drawingEnemy = false;
            self.drawingEnemyPack = false;
            self.resetCurrentMouseoverLayer();
        });
        this.currentMouseoverLayer = null;
        this.currentMouseoverLayerStyle = null;
        // Attach the monitor to each existing layer
        // this.map.leafletMap.on('layeradd', (this.onLayerCreated).bind(this));
        // this.map.leafletMap.eachLayer((this.monitorLayer).bind(this));

        this.map.leafletMap.on('mousemove', function (e) {
            // let layers = leafletPip.pointInLayer(e.latlng, self.map.leafletMap);

            if (self.drawingEnemy) {
                let isMouseStillInLayer = false;
                self.map.leafletMap.eachLayer(function (layer) {
                    // Only track this when we're 'ghosting' an enemy around to place it somewhere
                    // Only polygons may be a target for enemies
                    if (layer instanceof L.Polygon) {
                        // If the mouse is currently in the polygon
                        if (gju.pointInPolygon({
                            type: 'Point',
                            coordinates: [e.latlng.lng, e.latlng.lat]
                        }, layer.toGeoJSON().geometry)) {
                            // If we just entered a new mouseover layer and weren't in one already
                            if (self.currentMouseoverLayer === null) {
                                // Save the options (shallow copy of the object)
                                self.currentMouseoverLayerStyle = Object.assign({}, layer.options);
                                self.currentMouseoverLayer = layer;
                                layer.setStyle({
                                    fillColor: c.map.admin.mapobject.colors.mouseoverAddEnemy,
                                    color: c.map.admin.mapobject.colors.mouseoverAddEnemyBorder
                                });
                                // Don't immediately reset it after we're done
                                isMouseStillInLayer = true;
                            }
                            // We're still in one
                            else if (self.currentMouseoverLayer === layer) {
                                isMouseStillInLayer = true;
                            }
                        }
                    }
                });

                // If we were in a layer but no longer
                if (!isMouseStillInLayer) {
                    self.resetCurrentMouseoverLayer();
                }
            }
        });

        // When an enemy is added to the map, set its enemypack to the current mouse over layer (if that exists).
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        enemyMapObjectGroup.register('object:add', this, function (addEvent) {
            if (self.currentMouseoverLayer !== null) {
                let mapObject = self.map.findMapObjectByLayer(self.currentMouseoverLayer);

                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject!');
                addEvent.data.object.enemy_pack_id = mapObject.id;
            }
        });

        // When a pack is created, own all objects that it was placed under
        let enemyPackMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PACK);
        // When an enemy pack is added..
        enemyPackMapObjectGroup.register('object:add', this, function (event) {
            // Gather some data
            let enemyPack = event.data.object;

            enemyPack.register('synced', enemyPack, function (syncedEvent) {
                let newEnemyPack = syncedEvent.context;
                let enemyPackPolygon = newEnemyPack.layer;
                // For each enemy we know of
                $.each(enemyMapObjectGroup.objects, function (i, enemy) {

                    // Check if it falls in the layer
                    let latLng = enemy.layer.getLatLng();
                    if (gju.pointInPolygon({
                        type: 'Point',
                        coordinates: [latLng.lng, latLng.lat]
                    }, enemyPackPolygon.toGeoJSON().geometry) && enemyMapObjectGroup.isMapObjectVisible(enemy)) {
                        // Only if something changed; we don't want to make unnecessary requests
                        if (enemy.enemy_pack_id !== newEnemyPack.id && !enemy.is_mdt) {
                            // Bind the enemies
                            enemy.enemy_pack_id = newEnemyPack.id;
                            // Save all enemies so their pack connection is never broken
                            enemy.save();
                        }
                    }
                });
            });
        });
    }

    /**
     * Resets the current mouse over layer, if we have it
     */
    resetCurrentMouseoverLayer() {
        console.assert(this instanceof EnemyAttaching, this, 'this is not an instance of EnemyAttaching');

        if (this.currentMouseoverLayer !== null) {
            // No longer in this layer, revert changes
            this.currentMouseoverLayer.setStyle({
                fillColor: this.currentMouseoverLayerStyle.fillColor,
                color: this.currentMouseoverLayerStyle.color,
            });
            this.currentMouseoverLayer = null;
        }
    }

    /**
     * Wrapper so that 'this' is set properly.
     * @param event
     */
    onLayerCreated(event) {
        console.log(">> onLayerCreated", event);
        console.assert(this instanceof EnemyAttaching, this, 'this is not an instance of EnemyAttaching');
        // Only listen to created layers of enemy packs
        if (event.layer instanceof L.Polygon) {
            this.monitorLayer(event.layer);
        }
        console.log("OK onLayerCreated", event);
    }

    monitorLayer(layer) {
        console.log(">> monitorLayer", layer);
        console.assert(this instanceof EnemyAttaching, this, 'this is not an instance of EnemyAttaching');
        let self = this;

        console.log('attached to layer ' + layer);
        layer.on('mouseover', function (e) {
            console.log(">> mouseover", e);
            // Only track this when we're 'ghosting' an enemy around to place it somewhere
            if (self.drawingEnemy) {
                layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.mouseoverAddEnemy,
                    color: c.map.admin.mapobject.colors.mouseoverAddEnemyBorder
                });
                self.currentMouseoverLayer = layer;
            }
            console.log("OK mouseover", e);
        });
        console.log("OK monitorLayer", layer);
    }
}