/** @var dungeonMap object */

class AdminDungeonMap extends DungeonMap {

    refreshLeafletMap() {
        super.refreshLeafletMap();

        let verboseEvents = true;

        let self = this;

        // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
        this.enemyPackClassName = "AdminEnemyPack";
        this.enemyClassName = "AdminEnemy";
        this.enemyAttaching = new EnemyAttaching(this);

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
                    //     color: c.map.admin.mapobject.colors.unsaved
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
            self._drawnItems.addLayer(layer);

            if (layer instanceof L.Polygon) {
                self.addEnemyPack(layer);
            } else if (layer instanceof L.CircleMarker) {
                self.addEnemy(layer);
            }
        });

        // Set all edited layers to no longer be synced.
        this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
            let layers = e.layers;
            layers.eachLayer(function (layer) {
                console.log(layer, "Edited a layer!");
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject');
                mapObject.setSynced(false);
            });
        });

        this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
            console.log(L.Draw.Event.DRAWSTART, e);
        });

        if (verboseEvents) {
            this.leafletMap.on('layeradd', function (e) {
                console.log('layeradd', e);
            });

            this.leafletMap.on(L.Draw.Event.CREATED, function (e) {
                console.log(L.Draw.Event.CREATED, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
                console.log(L.Draw.Event.EDITED, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETED, function (e) {
                console.log(L.Draw.Event.DELETED, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
                console.log(L.Draw.Event.DRAWSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
                console.log(L.Draw.Event.DRAWSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWVERTEX, function (e) {
                console.log(L.Draw.Event.DRAWVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTART, function (e) {
                console.log(L.Draw.Event.EDITSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITMOVE, function (e) {
                console.log(L.Draw.Event.EDITMOVE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITRESIZE, function (e) {
                console.log(L.Draw.Event.EDITRESIZE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITVERTEX, function (e) {
                console.log(L.Draw.Event.EDITVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
                console.log(L.Draw.Event.EDITSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTART, function (e) {
                console.log(L.Draw.Event.DELETESTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTOP, function (e) {
                console.log(L.Draw.Event.DELETESTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBAROPENED, function (e) {
                console.log(L.Draw.Event.TOOLBAROPENED, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBARCLOSED, function (e) {
                console.log(L.Draw.Event.TOOLBARCLOSED, e);
            });
            this.leafletMap.on(L.Draw.Event.MARKERCONTEXT, function (e) {
                console.log(L.Draw.Event.MARKERCONTEXT, e);
            });
        }

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