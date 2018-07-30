class AdminDrawControls extends DrawControls {
    constructor(map, drawnItemsLayer) {
        super(map, drawnItemsLayer);

        // Add to the existing options
        $.extend(true, this.drawControlOptions, {
            draw: {
                polyline: false,
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
                circlemarker: {
                    icon: new L.DivIcon({
                        iconSize: new L.Point(10, 10),
                        className: 'leaflet-div-icon leaflet-editing-icon my-own-class'
                    }),
                }
            }
        });
    }
}