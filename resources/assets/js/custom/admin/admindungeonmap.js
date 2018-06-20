/** @var dungeonMap object */

class AdminDungeonMap extends DungeonMap {

    refreshLeafletMap() {
        super.refreshLeafletMap();

        let self = this;

        // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
        this.enemyPackClassName = "AdminEnemyPack";
        this.enemyClassName = "AdminEnemy";

        this._drawnItems = new L.FeatureGroup();
        this.leafletMap.addLayer(this._drawnItems);

        L.drawLocal.draw.toolbar.buttons.polygon = 'Draw an enemy group';
        L.drawLocal.draw.toolbar.buttons.circlemarker = 'Draw an enemy';

        let options = {
            position: 'topleft',
            draw: {
                polyline: false,
                // polyline: {
                //     shapeOptions: {
                //         color: '#f357a1',
                //         weight: 10
                //     }
                // },
                polygon: {
                    allowIntersection: false, // Restricts shapes to simple polygons
                    drawError: {
                        color: '#e1e100', // Color the shape will turn when intersects
                        message: '<strong>Oh snap!<strong> you can\'t draw that!' // Message that will show when intersect
                    },
                    // shapeOptions: {
                    //     color: c.map.admin.enemypack.colors.unsaved
                    // }
                },
                rectangle: false,
                circle: false,
                marker: false,
                circlemarker: {
                    icon: new L.DivIcon({
                        iconSize: new L.Point(10, 10),
                        className: 'leaflet-div-icon leaflet-editing-icon my-own-class'
                    }),
                }
            },
            edit: {
                featureGroup: this._drawnItems, //REQUIRED!!
                remove: true
            }
        };

        // Make sure it does not get added multiple times
        console.log(typeof this._drawControl);
        if (typeof this._drawControl === 'object') {
            this.leafletMap.removeControl(this._drawControl);
        }

        this._drawControl = new L.Control.Draw(options);
        this.leafletMap.addControl(this._drawControl);

        // If we created something
        this.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            let layer = event.layer;
            console.log(layer, "L.Draw.Event.CREATED");
            self._drawnItems.addLayer(layer);

            if( layer instanceof L.Polygon ){
                self.addEnemyPack(layer);
            } else if( layer instanceof L.CircleMarker ){
                self.addEnemy(layer);
            }
        });

        // Set all edited layers to no longer be synced.
        this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
            let layers = e.layers;
            layers.eachLayer(function (layer) {
                console.log(layer, "Editted a layer!");
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject');
                mapObject.setSynced(false);
            });
        });
    }

    addEnemyPack(layer) {
        console.assert(this instanceof AdminDungeonMap, this, 'this was not an AdminDungeonMap');
        let enemyPack = super.addEnemyPack(layer);
        // Just created, not synced!
        enemyPack.setSynced(false);

        this._drawnItems.addLayer(layer);
        return enemyPack;
    }

    addEnemy(layer) {
        console.assert(this instanceof AdminDungeonMap, this, 'this was not an AdminDungeonMap');
        let enemy = super.addEnemy(layer);
        // Just created, not synced!
        enemy.setSynced(false);

        this._drawnItems.addLayer(layer);
        return enemy;
    }
}