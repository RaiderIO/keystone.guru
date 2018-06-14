$(function () {
    onMapInitialized(adminInitControls);
    onEnemyPackCreated(adminCreateEnemyPack);
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

function adminCreateEnemyPack(enemyPack) {
    $.extend(enemyPack, {
        synced: false,
        saving: false,
        onLayerInit: function (layer) {
            // Create the context menu items
            let contextMenuItems = [{
                text: this.label,
                disabled: true
            }, {
                text: '<i class="fa fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
                disabled: this.synced || this.saving,
                callback: function () {
                    enemyPack.save();
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
        save: function () {
            $.ajax({
                type: 'POST',
                url: '/api/v1/enemypack',
                dataType: 'json',
                data: {
                    id: enemyPack.id,
                    floor_id: getCurrentFloor().id,
                    label: enemyPack.label,
                    vertices: enemyPack.getVertices()
                },
                beforeSend: function () {
                    console.log("beforeSend");
                    enemyPack.saving = true;
                    enemyPack.layer.setStyle({fillColor: c.map.admin.enemypack.colors.edited});
                },
                success: function (json) {
                    console.log(json);
                    enemyPack.id = json.id;
                    enemyPack.layer.setStyle({fillColor: c.map.admin.enemypack.colors.saved});
                },
                complete: function () {
                    console.log("complete");
                    enemyPack.saving = false;
                    enemyPack.layer.setStyle({fillColor: c.map.admin.enemypack.colors.unsaved});
                }
            });
        }
    });
}