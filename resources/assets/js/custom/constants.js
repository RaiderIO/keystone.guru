if (typeof Cookies.get('polyline_default_weight') === 'undefined') {
    Cookies.set('polyline_default_weight', 3);
}
if (typeof Cookies.get('hidden_map_object_groups') === 'undefined') {
    Cookies.set('hidden_map_object_groups', []);
}

let c = {
    paidtiers: {
        ad_free: 'ad-free',
        unlimited_dungeonroutes: 'unlimited-dungeonroutes',
        unlimited_routes: 'unlimited-routes',
        animated_polylines: 'animated-polylines'
    },
    map: {
        admin: {
            mapobject: {
                colors: {
                    mouseoverAddEnemy: '#5DE27F',
                    mouseoverAddEnemyBorder: '#347D47',
                }
            }
        },
        pridefulenemy: {
            max: 5,
            isEnabled: function () {
                // Shadowlands dungeons only
                return [28, 29, 30, 31, 32, 33, 34, 35].includes(getState().getMapContext().getDungeon().id);
            }
        },
        enemy: {
            /**
             * At whatever zoom various modifiers are displayed on the map
             */
            classification_display_zoom: 3,
            truesight_display_zoom: 3,
            teeming_display_zoom: 3,
            awakened_display_zoom: 3,
            inspiring_display_zoom: 3,
            colors: [
                /*'#C000F0',
                '#E25D5D',
                '#5DE27F'*/
                'green', 'yellow', 'orange', 'red', 'purple'
            ],
            minSize: function () {
                let result = getState().getMapContext().getMinEnemySizeDefault();

                let currentFloor = getState().getCurrentFloor();

                if (currentFloor.min_enemy_size !== null) {
                    result = currentFloor.min_enemy_size;
                }

                return result;
            },
            maxSize: function () {
                let result = getState().getMapContext().getMaxEnemySizeDefault();

                let currentFloor = getState().getCurrentFloor();

                if (currentFloor.max_enemy_size !== null) {
                    result = currentFloor.max_enemy_size;
                }

                return result;
            },
            margin: 2,
            calculateMargin: function (size) {
                let range = c.map.enemy.maxSize() - c.map.enemy.minSize();
                let zeroBased = (size - c.map.enemy.minSize());
                return (zeroBased / range) * c.map.enemy.margin;
            },
            calculateSize: function (health, minHealth, maxHealth) {
                // Perhaps some enemies are below minHealth, should not be lower than it, nor higher than max health (bosses)
                health = Math.min(Math.max(health, minHealth), maxHealth);

                // Offset the min health
                health -= minHealth;
                maxHealth -= minHealth;

                // Scale factor
                let scale = getState().getMapZoomLevel() / 2.0;

                let result = (c.map.enemy.minSize() + ((health / maxHealth) * (c.map.enemy.maxSize() - c.map.enemy.minSize()))) * scale;
                // console.log(typeof result, result, typeof Math.floor(result), Math.floor(result));

                // Return the correct size
                return Math.floor(result);
            }
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
            margin: 2,
            arcSegments: function (nr) {
                return Math.max(5, (9 - nr) + (getState().getMapZoomLevel() * 2));
            },
            polygonOptions: {
                color: '#5993D2',
                weight: 1,
                fillOpacity: 0.3,
                opacity: 1
            },
        },
        enemypatrol: {
            // Function so that you could do custom stuff with it if you want
            defaultColor: function () {
                return '#E25D5D';
            }
        },
        /* These colors may be overriden by drawcontrols.js */
        path: {
            defaultColor: randomColor,
        },
        polyline: {
            defaultColor: randomColor,
            defaultColorAnimated: '#F00',
            defaultWeight: Cookies.get('polyline_default_weight'),
            minWeight: 1,
            maxWeight: 5,
            polylineOptionsAnimated: {
                opacity: 1,
                delay: 400,
                dashArray: [10, 20],
                // pulseColorLight: '#FFF',
                // pulseColorDark: '#000',
                hardwareAcceleration: true,
                use: L.polyline
            },
            awakenedObeliskGatewayPolylineColor: '#80FF1A',
            awakenedObeliskGatewayPolylineColorAnimated: '#244812',
            awakenedObeliskGatewayPolylineWeight: 3,
        },
        brushline: {
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
                color: Cookies.get('polyline_default_color'),
                weight: 1
            },
            polygonOptions: {
                color: randomColor, //Cookies.get('polyline_default_color'),
                weight: 2,
                fillOpacity: 0.3,
                opacity: 1,
            },
            // Whenever the killzone is selected or focused by the user to adjust it
            polygonOptionsSelected: {
                delay: 400,
                dashArray: [10, 20],
                pulseColorLight: '#FFF',
                pulseColorDark: '#000',
                hardwareAcceleration: true,
                use: L.polygon
            },
            margin: 2,
            arcSegments: function (nr) {
                return Math.max(5, (9 - nr) + (getState().getMapZoomLevel() * 2));
            }
        },
        placeholderColors: {},
        editsidebar: {
            pullGradient: {
                defaultHandlers: [[0, '#FF0000'], [100, '#00FF00']]
            }
        },
        colorPickerDefaultOptions: {
            theme: 'nano', // 'classic' or 'monolith', or 'nano',

            // Set in state manager when class colors are set
            swatches: [],

            components: {

                // Main components
                preview: true,
                opacity: true,
                hue: true,

                // Input / output Options
                interaction: {
                    hex: true,
                    rgba: true,
                    hsla: false,
                    hsva: false,
                    cmyk: false,
                    input: true,
                    clear: false,
                    save: true
                }
            }
        },
        echo: {
            tooltipFadeOutTimeout: 3000,
            mouseSendFrequency: 1000
        }
    }
};