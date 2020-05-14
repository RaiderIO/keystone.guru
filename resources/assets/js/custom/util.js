function getDistanceSquared(latLng1, latLng2) {
    return Math.pow(latLng1.lat - latLng2.lat, 2) + Math.pow(latLng1.lng - latLng2.lng, 2);
}

function getDistance(latLng1, latLng2) {
    return Math.sqrt(getDistanceSquared(latLng1, latLng2));
}

function _getHandlebarsTranslations() {
    let locale = lang.getLocale();
    return lang.messages[locale + '.messages'];
}

/** Some built-in caching since this function is called a lot */
let _defaultVariables = null;

/**
 * Get the default handlebars variables (all translations, etc.)
 */
function getHandlebarsDefaultVariables() {
    if (_defaultVariables === null) {
        _defaultVariables = $.extend(_getHandlebarsTranslations(), {
            is_map_admin: getState().isMapAdmin(),
            is_user_admin: isUserAdmin
        });
    }
    return _defaultVariables;
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