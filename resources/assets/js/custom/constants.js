var c = {
    map: {
        admin: {
            mapobject: {
                colors: {
                    unsaved: '#E25D5D',
                    unsavedBorder: '#7C3434',

                    edited: '#E2915D',
                    editedBorder: '#7C5034',

                    saved: '#5DE27F',
                    savedBorder: '#347D47',

                    mouseoverAddEnemy: '#5993D2',
                    mouseoverAddEnemyBorder: '#34577D',
                }
            }
        },
        enemy: {
            colors: [
                /*'#C000F0',
                '#E25D5D',
                '#5DE27F'*/
                'green', 'yellow', 'orange', 'red', 'purple'
            ]
        },
        adminenemy: {
            mdtPolylineOptions: {
                color: '#00FF00',
                weight: 1
            },
            mdtPolylineMismatchOptions: {
                color: '#FFA500',
                weight: 1
            }
        },
        enemypack: {
            colors: {
                unsaved: '#E25D5D',
                unsavedBorder: '#7C3434',

                edited: '#E2915D',
                editedBorder: '#7C5034',

                saved: '#5993D2',
                savedBorder: '#34577D'
            },
            margin: 1,
            arcSegments: function (nr) {
                return Math.max(3, 9 - nr);
            },
            polygonOptions: {
                color: '#9DFF56',
                weight: 1,
                fillOpacity: 0.3,
                opacity: 1
            },
        },
        enemypatrol: {
            defaultColor: '#E25D5D'
        },
        /* These colors may be overriden by drawcontrols.js */
        route: {
            defaultColor: '#9DFF56',
        },
        brushline: {
            defaultColor: '#9DFF56',
            defaultWeight: 3,
            /**
             * The minimum distance (squared) that a point must have before it's added to the line from the previous
             * point. This is to prevent points from being too close to eachother and reducing performance, increasing
             * bandwidth and storage in database (though that's not that big of a deal).
             **/
            minDrawDistanceSquared: 3
        },
        killzone: {
            colors: {
                unsavedBorder: '#E25D5D',

                editedBorder: '#E2915D',

                savedBorder: '#5DE27F',

                mouseoverAddObject: '#5993D2',
            },
            polylineOptions: {
                color: '#9DFF56',
                weight: 1
            },
            polygonOptions: {
                color: '#9DFF56',
                weight: 2,
                fillOpacity: 0.3,
                opacity: 1
            },
            arcSegments: function (nr) {
                return Math.max(3, 12 - nr);
            },
            margin: 1
        },
        placeholderColors: {}
    }
};