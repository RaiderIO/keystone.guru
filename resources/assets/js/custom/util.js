/** This is because PhpStorm won't shut up about how getState() is not defined. It really is defined in statemanager.blade.php */
if (typeof getState !== 'function') {
    /**
     *
     * @returns {StateManager}
     */
    function getState() {
        return false;
    }
}

// Gives access to Object.id() method which will uniquely identify an object
(function () {
    if (typeof Object.id == "undefined") {
        let id = 0;

        Object.id = function (o) {
            if (typeof o.__uniqueid == "undefined") {
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

/**
 * Converts 'This is a text' to 'this-is-a-text'.
 * @param text {string}
 * @returns {string}
 */
function convertToSlug(text) {
    return text
        .toLowerCase()
        .replace(/[^\w ]+/g, '')
        .replace(/ +/g, '-');
}

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
            is_map_admin: typeof getState === 'function' && getState() !== false ? getState().isMapAdmin() : false,
            is_user_admin: isUserAdmin, // Defined in sitescripts
            csrf_token: csrfToken // Defined in sitescripts
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
 * https://stackoverflow.com/a/55292366/771270
 * @param str
 * @param ch
 * @returns {string|*}
 */
function trimEnd(str, ch) {
    let end = str.length;

    while (str[end - 1] === ch) {
        --end;
    }

    return (end < str.length) ? str.substring(0, end) : str;
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
    return hex.length === 1 ? "0" + hex : hex;
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

    return result.toLowerCase();
}

/**
 *
 * @param value {number}
 * @param max {number}
 * @returns {number}
 */
function getFormattedPercentage(value, max) {
    let percent = ((value / max) * 100);
    // Round to 1 decimal at best
    return Math.round(percent * 10) / 10;
}

/**
 *
 * @param value
 * @param $input
 * @param timeoutMS
 */
function copyToClipboard(value, $input = null, timeoutMS = null) {
    // https://codepen.io/shaikmaqsood/pen/XmydxJ
    let $temp = null;
    if ($input === null) {
        $temp = $('<input>');
        $('body').append($temp);
        $temp.val(value);
        $temp.select();
    } else {
        $input.select();
    }
    document.execCommand('copy');
    if ($input === null) {
        $temp.remove();
    }

    let opts = {};
    if (timeoutMS !== null) {
        opts.timeout = timeoutMS;
    }
    showInfoNotification(lang.get('messages.copied_to_clipboard'), opts);
}

/**
 * https://stackoverflow.com/questions/175739/built-in-way-in-javascript-to-check-if-a-string-is-a-valid-number
 * @param str
 * @returns {boolean}
 */
function isNumeric(str) {
    if (typeof str != "string") return false // we only process strings!
    return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
        !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
}

/**
 * @param latLngs
 * @see https://stackoverflow.com/questions/22796520/finding-the-center-of-leaflet-polygon
 * @return {object}
 */
function getCenteroid(latLngs) {
    let reduce = latLngs.reduce(function (x, y) {
        return [x[0] + y[0] / latLngs.length, x[1] + y[1] / latLngs.length]
    }, [0, 0]);

    return L.latLng(reduce[0], reduce[1]);
}


function getEnemies() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
}

function getEnemyPacks() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PACK);
}

function getEnemyPatrols() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PATROL);
}

function getKillZones() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
}

function getPaths() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PATH);
}

function getBrushlines() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_BRUSHLINE);
}

function getMapIcons() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);
}

/**
 * Helper functions to help debug the site.
 */
function getEnemy(id) {
    return getEnemies().findMapObjectById(id);
}

function getEnemyPack(id) {
    return getEnemyPacks().findMapObjectById(id);
}

function getEnemyPatrol(id) {
    return getEnemyPatrols().findMapObjectById(id);
}

function getKillZone(id) {
    return getKillZones().findMapObjectById(id);
}

function getPath(id) {
    return getPaths().findMapObjectById(id);
}

function getBrushline(id) {
    return getBrushlines().findMapObjectById(id);
}

function getMapIcon(id) {
    return getMapIcons().findMapObjectById(id);
}

$.fn.insertIndex = function (i) {
    // The element we want to swap with
    let $target = this.parent().children().eq(i);

    // Determine the direction of the appended index so we know what side to place it on
    if (this.index() > i) {
        $target.before(this);
    } else {
        $target.after(this);
    }

    return this;
};

// https://medium.com/talk-like/detecting-if-an-element-is-in-the-viewport-jquery-a6a4405a3ea2
$.fn.isInViewport = function () {
    let elementTop = $(this).offset().top;
    let elementBottom = elementTop + $(this).outerHeight();
    let viewportTop = $(window).scrollTop();
    let viewportBottom = viewportTop + $(window).height();
    return elementBottom > viewportTop && elementTop < viewportBottom;
};