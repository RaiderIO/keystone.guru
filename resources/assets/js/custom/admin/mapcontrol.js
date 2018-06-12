var _enemyPacks = [];

$(function () {
    onMapInitialized(adminInitControls);
});

function adminInitControls(map) {
    console.log(">> adminInitControls");

    var drawnItems = new L.FeatureGroup();
    mapObj.addLayer(drawnItems);

    L.drawLocal.draw.toolbar.buttons.polygon = 'Draw an enemy group';

    var options = {
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

    var drawControl = new L.Control.Draw(options);
    mapObj.addControl(drawControl);

    mapObj.on(L.Draw.Event.CREATED, function (event) {
        var layer = event.layer;
        var enemyPack = createEnemyPack(layer);
        enemyPack.addToFeatureGroup(drawnItems);

        drawnItems.addLayer(layer);
    });

    console.log("OK adminInitControls");
}

/**
 * Creates a pack of enemies from a newly created layer.
 * @param layer
 */
function createEnemyPack(layer) {
    var enemyPack = {
        id: 0,
        layer: layer,
        label: 'Mob pack #' + _enemyPacks.length,
        synced: false,
        addToFeatureGroup: function (fg) {
            fg.addLayer(layer);
            layer.bindContextMenu({
                contextmenuWidth: 140,
                contextmenuItems: [{
                    text: this.label,
                    disabled: true
                    // callback: ''
                }, {
                    text: '<i class="fa fa-save"></i> Save',
                    disabled: this.synced,
                    callback: function() {
                        saveEnemyPack(enemyPack);
                    }
                }, '-',
                    // {
                    //     text: 'Zoom in',
                    //     icon: 'images/zoom-in.png',
                    //     // callback: zoomIn
                    // }, {
                    //     text: 'Zoom out',
                    //     icon: 'images/zoom-out.png',
                    //     // callback: zoomOut
                    // }
                ]
            });

            // console.log(layer.toGeoJSON().geometry.coordinates[0]);
            layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
        }
    };
    _enemyPacks.push(enemyPack);

    return enemyPack;
}

function saveEnemyPack(pack){
    console.log("Pack:", pack);
}