// noinspection JSUnusedGlobalSymbols

let cookieDefaultAttributes = {path: '/', sameSite: 'None', secure: true};
Cookies.withAttributes(cookieDefaultAttributes);

// @TODO: temporary solution for ensuring default values for certain cookies are set
let cookieDefaults = {
    polyline_default_weight: 3,
    polyline_default_color: null,
    hidden_map_object_groups: '["mountablearea"]',
    hidden_map_object_groups_added_mountablearea: 0,
    map_number_style: 'enemy_forces',
    kill_zones_number_style: 'percentage',
    pulls_sidebar_floor_switch_visibility: 1,
    dungeon_speedrun_required_npcs_show_all: 0,
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

// If we need to initially hide the mountable areas, we don't want it to be visible by default
if (Cookies.get('hidden_map_object_groups_added_mountablearea') === '0') {
    try {
        let hiddenMapObjectGroups = JSON.parse(Cookies.get('hidden_map_object_groups'));
        hiddenMapObjectGroups.push('mountablearea');
        Cookies.set('hidden_map_object_groups', JSON.stringify(hiddenMapObjectGroups), cookieDefaultAttributes);
        Cookies.set('hidden_map_object_groups_added_mountablearea', 1, cookieDefaultAttributes);
    } catch (e) {
        console.error(e);
    }
}


// Map object groups
const MAP_OBJECT_GROUP_USER_MOUSE_POSITION = 'mouseposition';
const MAP_OBJECT_GROUP_BRUSHLINE = 'brushline';
const MAP_OBJECT_GROUP_ENEMY = 'enemy';
const MAP_OBJECT_GROUP_ENEMY_PATROL = 'enemypatrol';
const MAP_OBJECT_GROUP_ENEMY_PACK = 'enemypack';
const MAP_OBJECT_GROUP_FLOOR_UNION = 'floorunion';
const MAP_OBJECT_GROUP_FLOOR_UNION_AREA = 'floorunionarea';
const MAP_OBJECT_GROUP_KILLZONE = 'killzone';
const MAP_OBJECT_GROUP_KILLZONE_PATH = 'killzonepath';
const MAP_OBJECT_GROUP_MAPICON = 'mapicon';
const MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK = 'awakenedobeliskgatewaymapicon';
const MAP_OBJECT_GROUP_MOUNTABLE_AREA = 'mountablearea';
const MAP_OBJECT_GROUP_PATH = 'path';
const MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER = 'dungeonfloorswitchmarker';

const MAP_OBJECT_GROUP_NAMES = [
    MAP_OBJECT_GROUP_USER_MOUSE_POSITION,
    MAP_OBJECT_GROUP_ENEMY_PATROL,
    // Depends on MAP_OBJECT_GROUP_ENEMY_PATROL
    MAP_OBJECT_GROUP_ENEMY,
    // Depends on MAP_OBJECT_GROUP_ENEMY
    MAP_OBJECT_GROUP_ENEMY_PACK,
    MAP_OBJECT_GROUP_PATH,
    MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER,
    MAP_OBJECT_GROUP_BRUSHLINE,
    MAP_OBJECT_GROUP_MAPICON,
    // MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK is missing on purpose; it's an alias for MAPICON
    // Depends on MAP_OBJECT_GROUP_ENEMY, MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER
    MAP_OBJECT_GROUP_KILLZONE,
    MAP_OBJECT_GROUP_KILLZONE_PATH,
    MAP_OBJECT_GROUP_MOUNTABLE_AREA,
    MAP_OBJECT_GROUP_FLOOR_UNION,
    MAP_OBJECT_GROUP_FLOOR_UNION_AREA
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
const AFFIX_SHROUDED = 'Shrouded';
const AFFIX_AFFLICTED = 'Afflicted';
const AFFIX_ENTANGLING = 'Entangling';
const AFFIX_INCORPOREAL = 'Incorporeal';

// Dungeon Speedrun Required Npcs
const DUNGEON_DIFFICULTY_10_MAN = 1;
const DUNGEON_DIFFICULTY_25_MAN = 2;

// NPC Classifications
const NPC_CLASSIFICATION_ID_NORMAL = 1;
const NPC_CLASSIFICATION_ID_ELITE = 2;
const NPC_CLASSIFICATION_ID_BOSS = 3;
const NPC_CLASSIFICATION_ID_FINAL_BOSS = 4;
const NPC_CLASSIFICATION_ID_RARE = 5;

// NPC Types
const NPC_TYPE_ABERRATION = 1;
const NPC_TYPE_BEAST = 2;
const NPC_TYPE_CRITTER = 3;
const NPC_TYPE_DEMON = 4;
const NPC_TYPE_DRAGONKIN = 5;
const NPC_TYPE_ELEMENTAL = 6;
const NPC_TYPE_GIANT = 7;
const NPC_TYPE_HUMANOID = 8;
const NPC_TYPE_MECHANICAL = 9;
const NPC_TYPE_UNDEAD = 10;
const NPC_TYPE_UNCATEGORIZED = 11;

// Expansions
const EXPANSION_CLASSIC = 'classic';
const EXPANSION_TBC = 'tbc';
const EXPANSION_WOTLK = 'wotlk';
const EXPANSION_CATACLYSM = 'cata';
const EXPANSION_MOP = 'mop';
const EXPANSION_WOD = 'wod';
const EXPANSION_LEGION = 'legion';
const EXPANSION_BFA = 'bfa';
const EXPANSION_SHADOWLANDS = 'shadowlands';
const EXPANSION_DRAGONFLIGHT = 'dragonflight';

// Map icons
const MAP_ICON_TYPE_SPELL_BLOODLUST = 'spell_bloodlust';
const MAP_ICON_TYPE_SPELL_HEROISM = 'spell_heroism';
const MAP_ICON_TYPE_DUNGEON_START_ID = 10;

// Spells @TODO This should probably be dictated by the backend through MapContext
const SPELL_BLOODLUST = 2825;
const SPELL_HEROISM = 32182;
const SPELL_TIME_WARP = 80353;
const SPELL_FURY_OF_THE_ASPECTS = 390386;
const SPELL_ANCIENT_HYSTERIA = 90355;
const SPELL_PRIMAL_RAGE = 264667;
const SPELL_FERAL_HIDE_DRUMS = 381301;

const BLOODLUST_SPELLS = [
    SPELL_BLOODLUST,
    SPELL_HEROISM,
    SPELL_TIME_WARP,
    SPELL_FURY_OF_THE_ASPECTS,
    SPELL_ANCIENT_HYSTERIA,
    SPELL_PRIMAL_RAGE,
    SPELL_FERAL_HIDE_DRUMS
];

// Metrics
const METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY = 1;

const METRIC_TAG_MDT_COPY_VIEW = 'view';
const METRIC_TAG_MDT_COPY_EMBED = 'embed';

// Teeming states
const TEEMING_VISIBLE = 'visible';
const TEEMING_HIDDEN = 'hidden';

// Game versions
const GAME_VERSION_CLASSIC = 'classic';
const GAME_VERSION_WOTLK = 'wotlk';
const GAME_VERSION_RETAIL = 'retail';

// Leaflet constants
const LEAFLET_PANE_MAP = 'mapPane';
const LEAFLET_PANE_TILE = 'tilePane';
const LEAFLET_PANE_OVERLAY = 'overlayPane';
const LEAFLET_PANE_SHADOW = 'shadowPane';
const LEAFLET_PANE_MARKER = 'markerPane';
const LEAFLET_PANE_TOOLTIP = 'tooltipPane';
const LEAFLET_PANE_POPUP = 'popupPane';

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
    patreonbenefits: {
        ad_free: 'ad-free',
        unlimited_dungeonroutes: 'unlimited-dungeonroutes',
        unlimited_routes: 'unlimited-routes',
        animated_polylines: 'animated-polylines'
    },
    gameData: {
        scalingFactor: 1.08,
        scalingFactorPast10: 1.10,
        fortifiedScalingFactor: 1.2,
        tyrannicalScalingFactor: 1.3,
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
            boss_size_factor: 1.2,
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

                // Return the correct size
                return Math.ceil(result);
            },
            getKeyScalingFactor(keyLevel, fortified, tyrannical) {
                let keyLevelFactor = 1;
                // 2 because we start counting up at key level 3 (+2 = 0)
                for (let i = 2; i < keyLevel; i++) {
                    keyLevelFactor *= (i < 10 ? c.gameData.scalingFactor : c.gameData.scalingFactorPast10);
                }

                if (fortified) {
                    keyLevelFactor *= c.gameData.fortifiedScalingFactor;
                } else if (tyrannical) {
                    keyLevelFactor *= c.gameData.tyrannicalScalingFactor;
                }

                return Math.round(keyLevelFactor * 100) / 100;
            },
            calculateBaseHealthForKey(scaledHealth, keyLevel, fortified = false, tyrannical = false) {
                return Math.round(scaledHealth / c.map.enemy.getKeyScalingFactor(keyLevel, fortified, tyrannical));
            },
            calculateHealthForKey(baseHealth, keyLevel, fortified = false, tyrannical = false) {
                return Math.round(baseHealth * c.map.enemy.getKeyScalingFactor(keyLevel, fortified, tyrannical));
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
        adminenemypatrol: {
            polylineOptions: {
                color: '#730099',
                weight: 2,
                opacity: 1,
            },
        },
        enemypack: {
            // Function so that you could do custom stuff with it if you want
            defaultColor: function () {
                return '#5993D2';
            },
            margin: 2,
            arcSegments: function (nr) {
                return Math.max(5, (9 - nr) + (getState().getMapZoomLevel() * 2));
            },
            polygonOptions: {
                weight: 1,
                fillOpacity: 0.3,
                opacity: 1
            },
            polylineOptionsAnimated: {
                opacity: 1,
                delay: 400,
                dashArray: [10, 20],
                // pulseColorLight: '#FFF',
                // pulseColorDark: '#000',
                hardwareAcceleration: true,
                use: L.polyline
            },
        },
        enemypatrol: {
            // Function so that you could do custom stuff with it if you want
            defaultColor: function () {
                return '#003280';
            }, // #003280
            defaultWeight: 2,

            polylineOptions: {
                color: '#090',
                weight: 2,
                opacity: 0,
            },

            polylineOptionsHighlighted: {
                opacity: 1,
                weight: 4,
            },

            polylineDecoratorOptions: {
                fillOpacity: 0.5,
                opacity: 0.5,
                weight: 2,
            },

            polylineDecoratorOptionsHighlighted: {
                fillOpacity: 1,
                opacity: 1,
                weight: 4,
            },
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
            // Function so that you could do custom stuff with it if you want
            defaultColor: function () {
                return '#5993D2';
            },
            percentage_display_zoom_default: 3,
            getCurrentFloorPercentageDisplayZoom: function () {
                return getState().getCurrentFloor().percentage_display_zoom ?? c.map.killzone.percentage_display_zoom_default;
            },
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
        mountablearea: {
            color: '#eb4934'
        },
        floorunionarea: {
            color: '#00b08c',
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
