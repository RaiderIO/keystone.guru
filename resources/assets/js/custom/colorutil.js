

const randomColor = () => {
    function c() {
        let hex = Math.floor(Math.random() * 256).toString(16);
        return ("0" + String(hex)).substr(-2); // pad with zero
    }

    return "#" + c() + c() + c();
};

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
    // Catch in case the color is bad
    if (hex.length === 0) {
        hex = '#000';
    }

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
 * @param rgb {{r: int, g: int, b: int, a: int}}
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
 * Code for checking if a color is "brownish" so we can exclude it from randomly generated pulls
 */


// https://stackoverflow.com/a/54024653/1487756
function hsv2rgb(h, s, v) {
    let f = (n, k = (n + h / 60) % 6) => v - v * s * Math.max(Math.min(k, 4 - k, 1), 0);
    return [f(5), f(3), f(1)];
}

// https://stackoverflow.com/a/54070620/1487756
function rgb2hsv(r, g, b) {
    let v = Math.max(r, g, b), n = v - Math.min(r, g, b);
    let h = n && ((v === r) ? (g - b) / n : ((v === g) ? 2 + (b - r) / n : 4 + (r - g) / n));
    return [60 * (h < 0 ? h + 6 : h), v && n / v, v];
}

const clrLkp = [["red", 0], ["vermilion", 15], ["brown", 20], ["orange", 30], ["safran", 45], ["yellow", 60], ["light green yellow", 75], ["green yellow", 90], ["limett", 105], ["dark green", 120], ["green", 120], ["light blue-green", 135], ["blue green", 150], ["green cyan", 165], ["cyan", 180], ["blaucyan", 195], ["green blue", 210], ["light green blue", 225], ["blue", 240], ["indigo", 255], ["violet", 270], ["blue magenta", 285], ["magenta", 300], ["red magenta", 315], ["blue red", 330], ["light blue red", 345]].reverse()

const hex2rgb = hex => {
    const parts = hex.slice(1).match(/../g);
    if (!parts || parts.length < 3) throw new Error('Invalid hex value');
    return parts.map(p => parseInt(p, 16));
}
const hsv2name = (h, s, v) => clrLkp.find(([clr, val]) => h >= val)[0];

/**
 * @param hex
 * @returns {String}
 */
const hex2name = hex => [hex2rgb, rgb2hsv, hsv2name].reduce((a, v) => v(...[a].flat()), hex);

const pad = v => (v + '0').slice(0, 2);
const rgb2hex = (r, g, b) => '#' + [r, g, b].map(Math.round).map(n => pad(n.toString(16))).join('');
/**
 * END Code for checking if a color is "brownish" so we can exclude it from randomly generated pulls
 */

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
