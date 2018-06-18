/** @var dungeonMap object */

function adminInitControls() {
    console.log(">> adminInitControls");

    // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
    dungeonMap.enemyPackClassName = "AdminEnemyPack";

    let drawnItems = new L.FeatureGroup();
    dungeonMap.leafletMap.addLayer(drawnItems);

    L.drawLocal.draw.toolbar.buttons.polygon = 'Draw an enemy group';

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
                    color: c.map.admin.enemypack.colors.unsaved
                }
            },
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false
        },
        edit: {
            featureGroup: drawnItems, //REQUIRED!!
            remove: true
        }
    };

    let drawControl = new L.Control.Draw(options);
    dungeonMap.leafletMap.addControl(drawControl);
    dungeonMap.leafletMap.on(L.Draw.Event.CREATED, function (event) {
        let layer = event.layer;
        dungeonMap.addEnemyPack(layer);
        drawnItems.addLayer(layer);
    });

    console.log("OK adminInitControls");
}