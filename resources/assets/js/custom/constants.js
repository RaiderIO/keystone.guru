// noinspection JSUnusedGlobalSymbols

let cookieDefaultAttributes = {path: '/', sameSite: 'None', secure: true};
Cookies.withAttributes(cookieDefaultAttributes);

// @TODO: temporary solution for ensuring default values for certain cookies are set
let cookieDefaults = {
    polyline_default_weight: 3,
    polyline_default_color: null,
    hidden_map_object_groups: '[]',
    map_number_style: 'enemy_forces',
    kill_zones_number_style: 'percentage',
    pulls_sidebar_floor_switch_visibility: 1,
    map_unkilled_enemy_opacity: '50',
    map_unkilled_important_enemy_opacity: '80',
    map_enemy_aggressiveness_border: 0,
    map_enemy_dangerous_border: 0,
    enemy_display_type: 'enemy_portrait',
    echo_cursors_enabled: 1,
    map_controls_show_hide_labels: 1
};

for (let name in cookieDefaults) {
    if (cookieDefaults.hasOwnProperty(name)) {
        let value = Cookies.get(name);
        // If not set at all, or set to empty, re-fill it to fix a bug
        if (typeof value === 'undefined' || (name === 'hidden_map_object_groups' && value === '')) {
            Cookies.set(name, cookieDefaults[name], cookieDefaultAttributes);
        } else {
            // Re-set the cookie with the default attributes so that they're always up-to-date
            Cookies.set(name, value, cookieDefaultAttributes);
        }
    }
}

// Map object groups
const MAP_OBJECT_GROUP_USER_MOUSE_POSITION = 'mouseposition';
const MAP_OBJECT_GROUP_ENEMY = 'enemy';
const MAP_OBJECT_GROUP_ENEMY_PATROL = 'enemypatrol';
const MAP_OBJECT_GROUP_ENEMY_PACK = 'enemypack';
const MAP_OBJECT_GROUP_PATH = 'path';
const MAP_OBJECT_GROUP_KILLZONE = 'killzone';
const MAP_OBJECT_GROUP_KILLZONE_PATH = 'killzonepath';
const MAP_OBJECT_GROUP_BRUSHLINE = 'brushline';
const MAP_OBJECT_GROUP_MAPICON = 'mapicon';
const MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK = 'awakenedobeliskgatewaymapicon';
const MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER = 'dungeonfloorswitchmarker';

const MAP_OBJECT_GROUP_NAMES = [
    MAP_OBJECT_GROUP_USER_MOUSE_POSITION,
    MAP_OBJECT_GROUP_ENEMY,
    MAP_OBJECT_GROUP_ENEMY_PATROL,
    // Depends on MAP_OBJECT_GROUP_ENEMY
    MAP_OBJECT_GROUP_ENEMY_PACK,
    MAP_OBJECT_GROUP_PATH,
    MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER,
    MAP_OBJECT_GROUP_BRUSHLINE,
    MAP_OBJECT_GROUP_MAPICON,
    // MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK is missing on purpose; it's an alias for MAPICON
    // Depends on MAP_OBJECT_GROUP_ENEMY, MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER
    MAP_OBJECT_GROUP_KILLZONE,
    MAP_OBJECT_GROUP_KILLZONE_PATH
];

// Kill zones
const NUMBER_STYLE_PERCENTAGE = 'percentage';
const NUMBER_STYLE_ENEMY_FORCES = 'enemy_forces';

// Affixes
const AFFIX_BOLSTERING = 'Bolstering';
const AFFIX_BURSTING = 'Bursting';
const AFFIX_EXPLOSIVE = 'Explosive';
const AFFIX_FORTIFIED = 'Fortified';
const AFFIX_GRIEVOUS = 'Grievous';
const AFFIX_INFESTED = 'Infested';
const AFFIX_NECROTIC = 'Necrotic';
const AFFIX_QUAKING = 'Quaking';
const AFFIX_RAGING = 'Raging';
const AFFIX_RELENTLESS = 'Relentless';
const AFFIX_SANGUINE = 'Sanguine';
const AFFIX_SKITTISH = 'Skittish';
const AFFIX_TEEMING = 'Teeming';
const AFFIX_TYRANNICAL = 'Tyrannical';
const AFFIX_VOLCANIC = 'Volcanic';
const AFFIX_REAPING = 'Reaping';
const AFFIX_BEGUILING = 'Beguiling';
const AFFIX_AWAKENED = 'Awakened';
const AFFIX_INSPIRING = 'Inspiring';
const AFFIX_SPITEFUL = 'Spiteful';
const AFFIX_STORMING = 'Storming';
const AFFIX_PRIDEFUL = 'Prideful';
const AFFIX_TORMENTED = 'Tormented';
const AFFIX_UNKNOWN = 'Unknown';
const AFFIX_INFERNAL = 'Infernal';
const AFFIX_ENCRYPTED = 'Encrypted';

// NPC Classifications
const NPC_CLASSIFICATION_ID_NORMAL = 1;
const NPC_CLASSIFICATION_ID_ELITE = 2;
const NPC_CLASSIFICATION_ID_BOSS = 3;
const NPC_CLASSIFICATION_ID_FINAL_BOSS = 4;

/**
 * Returns a function which returns the polyline_default_color cookie value or a random color if none was set.
 * @returns {(function(): *)|(function(): string)|*}
 */
function polylineDefaultColor() {
    let defaultColor = Cookies.get('polyline_default_color');
    if (defaultColor === null || defaultColor === 'null') {
        // Return the random color function
        return randomColor();
    } else {
        return defaultColor;
    }
}

let c = {
    paidtiers: {
        ad_free: 'ad-free',
        unlimited_dungeonroutes: 'unlimited-dungeonroutes',
        unlimited_routes: 'unlimited-routes',
        animated_polylines: 'animated-polylines'
    },
    map: {
        settings: {
            minZoom: 1,
            maxZoom: 5,
            zoomSnap: 0.2
        },
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
        mapicon: {
            calculateSize: function (value) {
                return Math.floor(value * Math.max(1, (getState().getMapZoomLevel() / 2.5)));
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
            encrypted_display_zoom: 3,
            inspiring_display_zoom: 3,
            prideful_display_zoom: 3,
            tormented_display_zoom: 3,
            active_aura_display_zoom: 3,
            colors: [
                /*'#C000F0',
                '#E25D5D',
                '#5DE27F'*/
                'green', 'yellow', 'orange', 'red', 'purple'
            ],
            mdt_size_factor: 0.5,
            boss_size_factor: 1.5,
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
                let scale = Math.max(1, getState().getMapZoomLevel() / 2.0);

                let result = (c.map.enemy.minSize() + ((health / maxHealth) * (c.map.enemy.maxSize() - c.map.enemy.minSize()))) * scale;
                // console.log(typeof result, result, typeof Math.floor(result), Math.floor(result));

                // Return the correct size
                return Math.ceil(result);
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
        path: {
            defaultColor: polylineDefaultColor,
        },
        polyline: {
            defaultColor: polylineDefaultColor,
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
            killzonepath: {
                color: 'red',
                colorAnimated: 'red',
                weight: 5,
            },
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
            percentage_display_zoom: 3,
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
                color: function (previousPullColor = null) {
                    let isPreviousColorDark = typeof previousPullColor === 'undefined' || previousPullColor === null ? false : isColorDark(previousPullColor);
                    let color = null;

                    // Generate colors until a color
                    do {
                        color = randomColor();
                    } while (isColorDark(color) === isPreviousColorDark);

                    return color;
                }, //Cookies.get('polyline_default_color'),
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
            // The amount of time that must pass before another mouse location is saved to be synced to others, in milliseconds
            mousePollFrequencyMs: 100,
            // How often to send the mouse frequency, in milliseconds
            mouseSendFrequencyMs: 500,
        }
    }
};
