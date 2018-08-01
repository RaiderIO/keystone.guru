$(function () {
    // L.DrawToolbar.prototype.options = {
    //     polyline: {},
    //     polygon: {},
    //     rectangle: {},
    //     circle: {},
    //     marker: {},
    //     enemy: {}
    // };

    L.DrawToolbar.prototype.getModeHandlers = function (map) {

        return [{
            enabled: this.options.polyline,
            handler: new L.Draw.Polyline(map, this.options.polyline),
            title: this.options.polyline.hasOwnProperty('title') ? this.options.polyline.title : L.drawLocal.draw.toolbar.buttons.polyline
        }, {
            enabled: this.options.polygon,
            handler: new L.Draw.Polygon(map, this.options.polygon),
            title: this.options.polygon.hasOwnProperty('title') ? this.options.polygon.title : L.drawLocal.draw.toolbar.buttons.polygon
        }, {
            enabled: this.options.rectangle,
            handler: new L.Draw.Rectangle(map, this.options.rectangle),
            title: this.options.rectangle.hasOwnProperty('title') ? this.options.rectangle.title : L.drawLocal.draw.toolbar.buttons.rectangle
        }, {
            enabled: this.options.circle,
            handler: new L.Draw.Circle(map, this.options.circle),
            title: this.options.circle.hasOwnProperty('title') ? this.options.circle.title : L.drawLocal.draw.toolbar.buttons.circle
        }, {
            enabled: this.options.marker,
            handler: new L.Draw.Marker(map, this.options.marker),
            title: this.options.marker.hasOwnProperty('title') ? this.options.marker.title : L.drawLocal.draw.toolbar.buttons.marker
        }, {
            enabled: this.options.circlemarker,
            handler: new L.Draw.CircleMarker(map, this.options.circlemarker),
            title: this.options.circlemarker.hasOwnProperty('title') ? this.options.circlemarker.title : L.drawLocal.draw.toolbar.buttons.circlemarker
        }, {
            enabled: this.options.enemypack,
            handler: new L.Draw.EnemyPack(map, this.options.enemypack),
            title: this.options.enemypack.hasOwnProperty('title') ? this.options.enemypack.title : L.drawLocal.draw.toolbar.buttons.enemypack
        }, {
            enabled: this.options.enemy,
            handler: new L.Draw.Enemy(map, this.options.enemy),
            title: this.options.enemy.hasOwnProperty('title') ? this.options.enemy.title : L.drawLocal.draw.toolbar.buttons.enemy
        }]
    };
});

class AdminDrawControls extends DrawControls {
    constructor(map, drawnItemsLayer) {
        super(map, drawnItemsLayer);

        // Add to the existing options
        $.extend(true, this.drawControlOptions, {
            draw: {
                polyline: false,
                enemypack: {
                    allowIntersection: false, // Restricts shapes to simple polygons
                    drawError: {
                        color: '#e1e100', // Color the shape will turn when intersects
                        message: '<strong>Oh snap!<strong> you can\'t draw that!' // Message that will show when intersect
                    },
                    faClass: 'fa-draw-polygon',
                    title: 'Draw an enemy pack'
                    // shapeOptions: {
                    //     color: c.map.admin.mapobject.colors.unsaved
                    // }
                },
                enemy: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-user',
                    title: 'Draw an enemy'
                }
            }
        });
    }

    addControl() {
        super.addControl();
    }
}