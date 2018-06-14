var _enemyPacks = [];

$(function () {
    onMapInitialized(adminInitControls);
});

function adminInitControls(map) {
    console.log(">> adminInitControls");

    let drawnItems = new L.FeatureGroup();
    mapObj.addLayer(drawnItems);

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
    mapObj.addControl(drawControl);

    mapObj.on(L.Draw.Event.CREATED, function (event) {
        let layer = event.layer;
        let enemyPack = createEnemyPack(layer);
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
    let enemyPack = {
        id: 0,
        layer: layer,
        label: 'Mob pack #' + _enemyPacks.length,
        synced: false,
        saving: false,
        addToFeatureGroup: function (fg) {
            fg.addLayer(layer);

            // Create the context menu items
            let contextMenuItems = [{
                text: this.label,
                disabled: true
            }, {
                text: '<i class="fa fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
                disabled: this.synced || this.saving,
                callback: function () {
                    saveEnemyPack(enemyPack);
                }
            }];

            // Create the context menu
            layer.bindContextMenu({
                contextmenuWidth: 140,
                contextmenuItems: contextMenuItems
            });

            // Show a permantent tooltip for the pack's name
            layer.bindTooltip(this.label, {permanent: true, offset: [0, 0]}).openTooltip();
        },
        getVertices: function () {
            let coordinates = this.layer.toGeoJSON().geometry.coordinates[0];
            let result = [];
            for (let i = 0; i < coordinates.length; i++) {
                result.push({x: coordinates[i][0], y: coordinates[i][1]});
            }
            return result;
        }
    };
    _enemyPacks.push(enemyPack);

    return enemyPack;
}

function saveEnemyPack(pack) {
    $.ajax({
        type: 'POST',
        url: '/api/v1/enemypack',
        dataType: 'json',
        data: {
            id: pack.id,
            floor_id: getCurrentFloor().id,
            label: pack.label,
            vertices: pack.getVertices()
        },
        beforeSend: function () {
            console.log("beforeSend");
            pack.saving = true;
        },
        success: function (json) {
            console.log(json);
            if (json.result === "success") {
                pack.id = json.id;
            }
        },
        complete: function () {
            console.log("complete");
            pack.saving = false;
        }
    });
    console.log("Pack:", pack);
}