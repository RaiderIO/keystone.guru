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
                'green', 'yellow', 'orange', 'red', 'purple']
        },
        enemypack: {
            colors: {
                unsaved: '#E25D5D',
                unsavedBorder: '#7C3434',

                edited: '#E2915D',
                editedBorder: '#7C5034',

                saved: '#5993D2',
                savedBorder: '#34577D'
            }
        },
        enemypatrol : {
            defaultColor: '#E25D5D'
        },
        route: {
            defaultColor: '#9dff56',

        },
        killzone: {
            colors: {
                unsavedBorder: '#E25D5D',

                editedBorder: '#E2915D',

                savedBorder: '#5DE27F',

                mouseoverAddObject: '#5993D2',
            },
            polylineOptions: {
                color: 'red',
                weight: 1
            },
            polygonOptions: {
                color: 'hotpink',
                weight: 1,
                fillOpacity: 0.3,
                opacity: 1
            },
            arcSegments: 7,
            margin: 1
        },
        placeholderColors: {}
    }
};