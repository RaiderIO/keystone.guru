/** @var dungeonMap object */

let _drawnItems;

class AdminDungeonMap extends DungeonMap {


    refreshLeafletMap() {
        super.refreshLeafletMap();

        var self = this;

        // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
        this.enemyPackClassName = "AdminEnemyPack";

        _drawnItems = new L.FeatureGroup();
        this.leafletMap.addLayer(_drawnItems);

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
                    shapeOptions: {
                        color: c.map.admin.enemypack.colors.unsaved,
                        editing: {className: ""}
                    }
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
                featureGroup: _drawnItems, //REQUIRED!!
                remove: true
            }
        };

        let drawControl = new L.Control.Draw(options);
        this.leafletMap.addControl(drawControl);

        console.log('added listener');
        this.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            console.log("L.Draw.Event.CREATED");
            let layer = event.layer;
            // Create a new enemy pack from the newly created layer
            console.log("json: ", layer.toGeoJSON());
            _drawnItems.addLayer(layer);
            self.addEnemyPack(layer);
        });

        console.log("OK adminInitControls");
    }

    addEnemyPack(layer) {
        let enemyPack = super.addEnemyPack(layer);

        _drawnItems.addLayer(layer);
        return enemyPack;
    }
}