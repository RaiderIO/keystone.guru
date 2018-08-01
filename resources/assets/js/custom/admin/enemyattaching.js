class EnemyAttaching {
    constructor(map) {
        console.assert(map instanceof DungeonMap);
        let self = this;

        this.map = map;
        this.map.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
            // Drawing an enemy
            if (e.layerType === 'circlemarker') {
                self.drawingEnemy = true;
            }
        });
        this.currentMouseoverLayer = null;
        this.currentMouseoverLayerStyle = null
        // Attach the monitor to each existing layer
        // this.map.leafletMap.on('layeradd', (this.onLayerCreated).bind(this));
        // this.map.leafletMap.eachLayer((this.monitorLayer).bind(this));

        this.map.leafletMap.on('mousemove', function (e) {
            // let layers = leafletPip.pointInLayer(e.latlng, self.map.leafletMap);

            let isMouseStillInLayer = false;
            self.map.leafletMap.eachLayer(function (layer) {
                // Only track this when we're 'ghosting' an enemy around to place it somewhere
                // Only polygons may be a target for enemies
                if (self.drawingEnemy && layer instanceof L.Polygon) {
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
            if (self.currentMouseoverLayer !== null && !isMouseStillInLayer) {
                // No longer in this layer, revert changes
                self.currentMouseoverLayer.setStyle({
                    fillColor: self.currentMouseoverLayerStyle.fillColor,
                    color: self.currentMouseoverLayerStyle.color,
                });
                self.currentMouseoverLayer = null;
            }
        });


        // When an enemy is added to the map, set its enemypack to the current mouse over layer (if that exists).
        this.map.register('enemy:add', function (event) {
            if (self.currentMouseoverLayer !== null) {
                let mapObject = self.map.findMapObjectByLayer(self.currentMouseoverLayer);

                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject!');
                event.data.enemy.enemypack = mapObject;
            }
        });

        this.map.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
            // Whatever we were drawing, we're not anymore so don't do any check for what layerType it is
            self.drawingEnemy = false;
            console.log("DRAWSTOP", self.currentMouseoverLayer, e);
            if (typeof self.currentMouseoverLayer !== 'undefined') {
                console.log('assign enemy to ' + self.currentMouseoverLayer);
            }
        });
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