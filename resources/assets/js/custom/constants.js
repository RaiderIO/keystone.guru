// noinspection JSUnusedGlobalSymbols
let cookieDefaultAttributes = undefined;

// Environments
const ENVIRONMENT_LOCAL = 'local';

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

// Map
const MAP_FACADE_STYLE_SPLIT_FLOORS = 'split_floors';
const MAP_FACADE_STYLE_FACADE = 'facade';
const MAP_FACADE_STYLE_BOTH = 'both';

// Map context
const MAP_CONTEXT_TYPE_DUNGEON_ROUTE = 'dungeonroute';
const MAP_CONTEXT_TYPE_LIVE_SESSION = 'livesession';
const MAP_CONTEXT_TYPE_MAPPING_VERSION_EDIT = 'mappingVersionEdit';
const MAP_CONTEXT_TYPE_DUNGEON_EXPLORE = 'dungeonExplore';

// Dungeons
const DUNGEON_SIEGE_OF_BORALUS = 'siegeofboralus';
const DUNGEON_THE_NEXUS = 'thenexus';

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
const AFFIX_THUNDERING = 'Thundering';
const AFFIX_AFFLICTED = 'Afflicted';
const AFFIX_ENTANGLING = 'Entangling';
const AFFIX_INCORPOREAL = 'Incorporeal';
const AFFIX_XALATATHS_BARGAIN_ASCENDANT = 'Xal\'atath\'s Bargain: Ascendant';
const AFFIX_XALATATHS_BARGAIN_DEVOUR = 'Xal\'atath\'s Bargain: Devour';
const AFFIX_XALATATHS_BARGAIN_VOIDBOUND = 'Xal\'atath\'s Bargain: Voidbound';
const AFFIX_XALATATHS_BARGAIN_OBLIVION = 'Xal\'atath\'s Bargain: Oblivion';
const AFFIX_XALATATHS_BARGAIN_FRENZIED = 'Xal\'atath\'s Bargain: Frenzied';
const AFFIX_XALATATHS_GUILE = 'Xal\'atath\'s Guile';
const AFFIX_CHALLENGERS_PERIL = 'Challenger\'s Peril';

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

// Seasonal types
let ENEMY_SEASONAL_TYPE_BEGUILING = 'beguiling';
let ENEMY_SEASONAL_TYPE_AWAKENED = 'awakened';
let ENEMY_SEASONAL_TYPE_INSPIRING = 'inspiring';
let ENEMY_SEASONAL_TYPE_PRIDEFUL = 'prideful';
let ENEMY_SEASONAL_TYPE_TORMENTED = 'tormented';
let ENEMY_SEASONAL_TYPE_ENCRYPTED = 'encrypted';
let ENEMY_SEASONAL_TYPE_MDT_PLACEHOLDER = 'mdt_placeholder';
let ENEMY_SEASONAL_TYPE_SHROUDED = 'shrouded';
let ENEMY_SEASONAL_TYPE_SHROUDED_ZUL_GAMUX = 'shrouded_zul_gamux';
let ENEMY_SEASONAL_TYPE_NO_SHROUDED = 'no_shrouded';

// Expansions
const EXPANSION_CLASSIC = 'classic';
const EXPANSION_TBC = 'tbc';
const EXPANSION_WOTLK = 'wotlk';
const EXPANSION_CATACLYSM = 'cata';
const EXPANSION_MOP = 'mop';
const EXPANSION_WOD = 'wod';
const EXPANSION_LEGION = 'legion';
const EXPANSION_BFA = 'bfa';
const EXPANSION_SHADOWLANDS = 'sl';
const EXPANSION_DRAGONFLIGHT = 'df';
const EXPANSION_TWW = 'tww';
const EXPANSION_MIDNIGHT = 'midnight';
const EXPANSION_THE_LAST_TITAN = 'tlt';

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

// Mountable Areas
const MOVEMENT_SPEED_DEFAULT = 7;
const MOVEMENT_SPEED_MOUNTED = 14;

const COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH = 'npc_death';
const COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH = 'player_death';
const COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL = 'player_spell';

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
        scalingFactor: 1.10,
        scalingFactorPast10: 1.10,
        fortifiedScalingFactor: 1.2,
        tyrannicalScalingFactor: 1.3,
        thunderingScalingFactor: 1.05,
        guileScalingFactor: 1.3,
    },
    map: {
        settings: {
            defaultMaxZoom: 5,
        },
        leafletSettings: {
            // Context menu when right clicking stuff
            contextmenu: true,
            zoomControl: false,
            minZoom: 1,
            maxZoom: 10,
            maxNativeZoom: 5,
            zoomSnap: 0,
            boxZoom: false,
            wheelDebounceTime: 100,
            wheelPxPerZoomLevel: 400
        },
        heatmapSettings: {
            minOpacity: 0.1,
            radius: 30
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
            getKeyScalingFactor(keyLevel, affixes = []) {
                let keyLevelFactor = 1;
                // 2 because we start counting up at key level 3 (+2 = 0)
                for (let i = 1; i < keyLevel; i++) {
                    keyLevelFactor *= (i < 10 ? c.gameData.scalingFactor : c.gameData.scalingFactorPast10);
                }

                if (affixes.includes(AFFIX_FORTIFIED)) {
                    keyLevelFactor *= c.gameData.fortifiedScalingFactor;
                }
                if (affixes.includes(AFFIX_TYRANNICAL)) {
                    keyLevelFactor *= c.gameData.tyrannicalScalingFactor;
                }
                if (affixes.includes(AFFIX_THUNDERING)) {
                    keyLevelFactor *= c.gameData.thunderingScalingFactor;
                }
                if (keyLevel >= 12 && affixes.includes(AFFIX_XALATATHS_GUILE)) {
                    keyLevelFactor *= c.gameData.guileScalingFactor;
                }

                return Math.round(keyLevelFactor * 100) / 100;
            },
            calculateBaseHealthForKey(scaledHealth, keyLevel, affixes = []) {
                return Math.round(scaledHealth / c.map.enemy.getKeyScalingFactor(keyLevel, affixes));
            },
            calculateHealthForKey(baseHealth, keyLevel, affixes = []) {
                return Math.round(baseHealth * c.map.enemy.getKeyScalingFactor(keyLevel, affixes));
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
        dungeonfloorswitchmarker: {
            floorUnionConnectionPolylineOptions: {
                color: '#006b77',
                opacity: 0.1,
                weight: 3,
            },
            floorUnionConnectionPolylineMouseoverOptions: {
                opacity: 1
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
                color: '#F00',
                colorAnimated: '#F00',
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

                    // Generate colors until a valid color pops up
                    do {
                        color = randomColor();
                    } while (isColorDark(color) === isPreviousColorDark || ['orange', 'brown', 'vermillion'].includes(hex2name(color)));

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
            },
            // Whenever the user clicks on a killzone, the map will pan to the killzone using these settings
            // https://leafletjs.com/reference.html#zoom/pan-options
            selectionSetViewOptions: {
                animate: true,
                duration: 1
            }
        },
        killZonePath: {
            defaultHandlers: [[0, '#ff0000'], [50, '#0000BB'], [100, '#00ff00']],
        },
        mountablearea: {
            color: '#eb4934',
            margin: 2,
            arcSegments: function (nr) {
                return Math.max(5, (9 - nr) + (getState().getMapZoomLevel() * 2));
            },
            polygonOptions: {
                color: '#eb4934',
                weight: 1,
                fillOpacity: 0.3,
                opacity: 1
            },
        },
        floorunion: {
            polygonOptions: {
                color: '#ff6200',
                weight: 1,
                fillOpacity: 0.3,
                opacity: 1
            },
        },
        floorunionarea: {
            color: '#00b08c',
        },
        placeholderColors: {},
        editsidebar: {
            pullGradient: {
                defaultHandlers: [[0, '#FF0000'], [100, '#00FF00']]
            },
            pullsWorkbench: {
                description: {
                    maxLength: 500,
                    warningThreshold: 0.75
                }
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
            tooltipFadeOutTimeoutMs: 3000,
            // The amount of time that must pass before another mouse location is saved to be synced to others, in milliseconds
            mousePollFrequencyMs: 100,
            // How often to send the mouse frequency, in milliseconds
            mouseSendFrequencyMs: 500,
            // The amount of users before an overflow is initiated
            userOverflowCount: 1
        },
        sanitizeTextDefaultAllowedTags: ['h4', 'h5', 'h6', 'b', 'i', 'br'],
        sanitizeText: function (text, convertLineEnding = true) {
            if (text === null || typeof text !== 'string') {
                return text;
            }

            let allowedTags = c.map.sanitizeTextDefaultAllowedTags;

            if (convertLineEnding === true) {
                text = text.replaceAll('\n', '<br>');

                if (!allowedTags.includes('br')) {
                    allowedTags.push('br');
                }
            }

            return filterHTML(text, allowedTags);
        }
    }
};
