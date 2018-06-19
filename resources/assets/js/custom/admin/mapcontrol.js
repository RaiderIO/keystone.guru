/** @var dungeonMap object */

function adminInitControls() {
    console.log(">> adminInitControls");

    // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
    dungeonMap.enemyPackClassName = "AdminEnemyPack";

    let drawnItems = new L.FeatureGroup();
    dungeonMap.leafletMap.addLayer(drawnItems);

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
                    editing: { className: ""}
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
            featureGroup: drawnItems, //REQUIRED!!
            remove: true
        }
    };

    let drawControl = new L.Control.Draw(options);
    dungeonMap.leafletMap.addControl(drawControl);

    console.log('added listener');
    dungeonMap.leafletMap.on(L.Draw.Event.CREATED, function (event) {
        let layer = event.layer;
        // Create a new enemy pack from the newly created layer
        console.log("json: ", layer.toGeoJSON());
        dungeonMap.addEnemyPack(layer);
    });
    dungeonMap.leafletMap.on('layeradd', function (event) {
        // Add all new layers to the edit layer
        let layer = event.layer;
        drawnItems.addLayer(layer);
    });


        let jsonLayer = L.geoJson(JSON.parse('{"type":"Feature","properties":{},"geometry":{"type":"Polygon","coordinates":[[[74.265229,-71.25],[82.516161,-80.5],[95.375877,-74.96405],[92.767743,-66.25],[70.015172,-59.75],[74.265229,-71.25]]]}}'), {
            onEachFeature: function (feature, layer) {
                console.log("layer class " + layer.constructor.name);
                drawnItems.addLayer(layer);
                // layer.bindContextMenu({
                //     contextmenu: true,
                //     contextmenuItems: [{
                //         text: 'Marker item'
                //     }]
                // });
            }
        });


        jsonLayer = L.geoJson(JSON.parse('{"type":"Feature","properties":{},"geometry":{"type":"Polygon","coordinates":[[[174.265229,31.25],[182.516161,20.5],[195.375877,26.96405],[192.767743,34.25],[170.015172,41.75],[74.265229,29.25]]]}}'), {
            onEachFeature: function (feature, layer) {
                console.log("layer class " + layer.constructor.name);
                drawnItems.addLayer(layer);
                // layer.bindContextMenu({
                //     contextmenu: true,
                //     contextmenuItems: [{
                //         text: 'Marker item'
                //     }]
                // });
            }
        });

    console.log("OK adminInitControls");
}