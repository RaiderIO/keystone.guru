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
                },
                enemy: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-user',
                    title: 'Draw an enemy'
                },
                enemypatrol: false,
                dungeonstartmarker: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-flag',
                    title: 'Draw a dungeon start marker'
                },
                dungeonfloorswitchmarker: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-door-open',
                    title: 'Draw a dungeon floor switch marker'
                }
            }
        });
    }
}