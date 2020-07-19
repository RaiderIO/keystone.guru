// Gives access to Object.id() method which will uniquely identify an object
(function() {
    if ( typeof Object.id == "undefined" ) {
        let id = 0;

        Object.id = function(o) {
            if ( typeof o.__uniqueid == "undefined" ) {
                Object.defineProperty(o, "__uniqueid", {
                    value: ++id,
                    enumerable: false,
                    // This could go either way, depending on your
                    // interpretation of what an "id" is
                    writable: false
                });
            }

            return o.__uniqueid;
        };
    }
})();

function getDistanceSquared(xy1, xy2) {
    return Math.pow(xy1[0] - xy2[0], 2) + Math.pow(xy1[1] - xy2[1], 2);
}

function getDistance(latLng1, latLng2) {
    return Math.sqrt(getLatLngDistanceSquared(latLng1, latLng2));
}

function getLatLngDistanceSquared(latLng1, latLng2) {
    return Math.pow(latLng1.lat - latLng2.lat, 2) + Math.pow(latLng1.lng - latLng2.lng, 2);
}

function getLatLngDistance(latLng1, latLng2) {
    return Math.sqrt(getLatLngDistanceSquared(latLng1, latLng2));
}

function _getHandlebarsTranslations() {
    let locale = lang.getLocale();
    return lang.messages[locale + '.messages'];
}

const randomColor = () => {
    function c() {
        let hex = Math.floor(Math.random() * 256).toString(16);
        return ("0" + String(hex)).substr(-2); // pad with zero
    }

    return "#" + c() + c() + c();
};

/** Some built-in caching since this function is called a lot */
let _defaultVariables = null;

/**
 * Get the default handlebars variables (all translations, etc.)
 */
function getHandlebarsDefaultVariables() {
    if (_defaultVariables === null) {
        _defaultVariables = $.extend({}, _getHandlebarsTranslations(), {
            is_map_admin: typeof getState !== 'function' ? false : getState().isMapAdmin(),
            is_user_admin: isUserAdmin
        });
    }
    return _defaultVariables;
}

/**
 * Hacked this myself to suit my needs.
 * @param str
 * @returns {*}
 */
function decodeHtmlEntity(str) {
    return str.replace(/&#x27;/g, '\'').replace(/&quot;/g, '"').replace(/&amp;/g, '&').replace(/&#x3D;/g, '=');
}

/**
 *
 * @param key {string}
 * @returns {string}
 */
function toSnakeCase(key) {
    let result = key.replace(/([A-Z])/g, " $1");
    return result.split(' ').join('_').toLowerCase().substring(1);
}

/**
 * Checks if a given hex color is 'dark' or not.
 * @param hex
 * @returns {boolean}
 */
function isColorDark(hex) {
    return getLuminance(hex) > 0.5
}

/**
 * https://stackoverflow.com/a/1855903/771270
 * Get the luminance of a hex color, where 1 is not luminant and 0 is bright.
 * @param hex
 * @returns {number}
 */
function getLuminance(hex) {
    let rgb = hexToRgb(hex);
    return 1 - (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255
}

/**
 *
 * @param hex
 * @returns {*}
 */
function hexToRgb(hex) {
    // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
    let shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
    hex = hex.replace(shorthandRegex, function (m, r, g, b) {
        return r + r + g + g + b + b;
    });

    let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}

function _componentToHex(c) {
    let hex = c.toString(16);
    return hex.length == 1 ? "0" + hex : hex;
}

/**
 * @param rgb [r, g, b]
 * @returns {string}
 */
function rgbToHex(rgb) {
    return "#" + _componentToHex(rgb.r) + _componentToHex(rgb.g) + _componentToHex(rgb.b);
}

/**
 * Parse an RGB string (such as rgba(171, 212, 115, 255)) to a parsed object.
 * @param rgbaString
 * @returns {{r: int, g: int, b: int, a: int}}
 */
function parseRgba(rgbaString) {
    let split = rgbaString.replace('rgba(', '').replace(')', '').replace(' ', '').split(',');
    return {r: parseInt(split[0]), g: parseInt(split[1]), b: parseInt(split[2]), a: parseInt(split[3])};
}

/**
 * Handlers is an array in the form of [[<0-100>, 'hex'], ....]
 * @param handlers
 * @param weight
 * @returns {[number, number, number]}
 */
function pickHexFromHandlers(handlers, weight) {
    console.assert(handlers.length > 1, 'Handlers.length <= 1!', handlers);

    // If color is before the start or after the end of any gradients, return last known color
    let result = null;
    if (handlers[0][0] >= weight) {
        result = handlers[0][1];
    } else if (handlers[handlers.length - 1][0] <= weight) {
        result = handlers[handlers.length - 1][1];
    } else {
        // Color is in between gradients now, determine which gradient it is
        let color1 = null;
        let color2 = null;
        let scaledWeight = 0;
        for (let i = 0; i < handlers.length; i++) {
            let a = handlers[i];
            let b = handlers[i + 1];
            if (weight >= a[0] && weight <= b[0]) {
                color1 = hexToRgb(a[1]);
                color2 = hexToRgb(b[1]);

                let gradientRange = b[0] - a[0];
                let weightOnGradientRange = weight - a[0];
                scaledWeight = (weightOnGradientRange / gradientRange);

                break;
            }
        }
        console.assert(color1 !== null, 'color1 === null!', handlers);
        console.assert(color2 !== null, 'color2 === null!', handlers);

        let invertedScaledWeight = 1 - scaledWeight;
        let rgb = {
            r: Math.round(color2.r * scaledWeight + color1.r * invertedScaledWeight),
            g: Math.round(color2.g * scaledWeight + color1.g * invertedScaledWeight),
            b: Math.round(color2.b * scaledWeight + color1.b * invertedScaledWeight)
        };
        result = rgbToHex(rgb);
    }

    return result;
}


/**
 * Helper functions to help debug the site.
 */
function getEnemy(id) {
    let mapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
    return mapObjectGroup.findMapObjectById(id);
}

function getEnemyPack(id) {
    let mapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PACK);
    return mapObjectGroup.findMapObjectById(id);
}

function getEnemyPatrol(id) {
    let mapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PATROL);
    return mapObjectGroup.findMapObjectById(id);
}

function getKillZone(id) {
    let mapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
    return mapObjectGroup.findMapObjectById(id);
}

function getPath(id) {
    let mapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PATH);
    return mapObjectGroup.findMapObjectById(id);
}

function getBrushline(id) {
    let mapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_BRUSHLINE);
    return mapObjectGroup.findMapObjectById(id);
}

function getMapIcon(id) {
    let mapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);
    return mapObjectGroup.findMapObjectById(id);
}