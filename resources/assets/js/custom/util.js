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

const randomColor = () => '#' + (Math.random() * 0xFFFFFF << 0).toString(16);

/** Some built-in caching since this function is called a lot */
let _defaultVariables = null;

/**
 * Get the default handlebars variables (all translations, etc.)
 */
function getHandlebarsDefaultVariables() {
    if (_defaultVariables === null) {
        _defaultVariables = $.extend(_getHandlebarsTranslations(), {
            is_map_admin: typeof getState !== 'function' ? false : getState().isMapAdmin(),
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

function _componentToHex(c) {
  var hex = c.toString(16);
  return hex.length == 1 ? "0" + hex : hex;
}

/**
 * @param rgb [r, g, b]
 * @returns {string}
 */
function rgbToHex(rgb) {
  return "#" + _componentToHex(rgb[0]) + _componentToHex(rgb[1]) + _componentToHex(rgb[2]);
}

/**
 * Handlers is an array in the form of [[<0-100>, 'hex'], ....]
 * @param handlers
 * @param weight
 * @returns {[number, number, number]}
 */
function pickHexFromHandlers(handlers, weight) {
    console.assert(handlers.length > 1, 'Handlers.length <= 1!', handlers);

    console.log('>> pickHexFromHandlers', handlers, weight);

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
            console.log(`${weight} -> ${a[0]} & ${b[0]}`)
            if (weight >= a[0] && weight <= b[0]) {
                console.log(`${weight} is between ${a[0]} and ${b[0]}`)
                color1 = hexToRgb(a[1]);
                color2 = hexToRgb(b[1]);

                let gradientRange = b[0] - a[0];
                let weightOnGradientRange = weight - a[0];
                scaledWeight = (weightOnGradientRange / gradientRange);

                console.log(a, b, weight, gradientRange, weightOnGradientRange, scaledWeight);
                break;
            }
        }
        console.assert(color1 !== null, 'color1 === null!', handlers);
        console.assert(color2 !== null, 'color2 === null!', handlers);

        let invertedScaledWeight = 1 - scaledWeight;
        let rgb = [Math.round(color2.r * scaledWeight + color1.r * invertedScaledWeight),
            Math.round(color2.g * scaledWeight + color1.g * invertedScaledWeight),
            Math.round(color2.b * scaledWeight + color1.b * invertedScaledWeight)];
        console.log(rgb);
        result = rgbToHex(rgb);
    }

    console.log('OK pickHexFromHandlers', result);
    return result;
}