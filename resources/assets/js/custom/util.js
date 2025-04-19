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

function createOffsetPolygon(vertices, offset, arcSegments, roundCornersOnly = false) {
    let latLngs = vertices.map(point => ({x: point.lng, y: point.lat}));

    // Snap first and last vertex together if they're within ~1 unit (e.g., degree/meters depending on projection)
    let lastPopped = false;
    if (latLngs.length > 1) {
        const first = latLngs[0];
        const last = latLngs[latLngs.length - 1];

        if (getDistanceSquared([first.x, first.y], [last.x, last.y]) < 1) {
            latLngs.pop();
            lastPopped = true;
        }
    }

    // Must have at least 2 points to create a polygon
    if (latLngs.length > 1) {
        try {
            // Ensure consistent winding: reverse if clockwise
            if (isPolygonClockwise(latLngs)) {
                latLngs.reverse();
            }

            latLngs = offsetPolygon(latLngs, offset, arcSegments);
            // Bring it back to how it was - roughly
            if (roundCornersOnly) {
                latLngs = offsetPolygon(latLngs, offset * -0.9, 0);
            }
        } catch (error) {
            // Not particularly interesting to spam the console with
            console.error('Unable to create offset for vertices', this.id, error, vertices, latLngs);
        }
    }

    // Check for non-NaN values
    latLngs = latLngs
        .filter(point => !isNaN(point.x) && !isNaN(point.y))
        .map(point => [point.y, point.x]);

    // Connect the line up again to the beginning
    if (lastPopped) {
        latLngs[latLngs.length - 1] = latLngs[0];
    }

    return latLngs;
}

function isPolygonClockwise(points) {
    let sum = 0;
    for (let i = 0; i < points.length; i++) {
        const curr = points[i];
        const next = points[(i + 1) % points.length];
        sum += (next.x - curr.x) * (next.y + curr.y);
    }
    return sum > 0; // If true → clockwise
}

function getDistance(xy1, xy2) {
    return Math.sqrt(getDistanceSquared(xy1, xy2));
}

function getDistanceSquared(xy1, xy2) {
    return Math.pow(xy1[0] - xy2[0], 2) + Math.pow(xy1[1] - xy2[1], 2);
}

function getLatLngDistanceSquared(latLng1, latLng2) {
    return Math.pow(latLng1.lat - latLng2.lat, 2) + Math.pow(latLng1.lng - latLng2.lng, 2);
}

function getLatLngDistance(latLng1, latLng2) {
    return Math.sqrt(getLatLngDistanceSquared(latLng1, latLng2));
}

function rotateLatLng(centerLatLng, latLng, degrees) {
    if (degrees === 0) {
        return latLng;
    }

    let lng1 = latLng.lng - centerLatLng.lng;
    let lat1 = latLng.lat - centerLatLng.lat;

    let angle = degrees * (Math.PI / 180);

    let lng2 = lng1 * Math.cos(angle) - lat1 * Math.sin(angle);
    let lat2 = lng1 * Math.sin(angle) + lat1 * Math.cos(angle);

    latLng.lng = lng2 + centerLatLng.lng;
    latLng.lat = lat2 + centerLatLng.lat;

    return latLng;
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
 *
 * @param value {number}
 * @param max {number}
 * @returns {number}
 */
function getFormattedPercentage(value, max) {
    if (max === 0) {
        return 0;
    }

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
    if (document.execCommand('copy')) {
        let opts = {};
        if (timeoutMS !== null) {
            opts.timeout = timeoutMS;
        }
        showInfoNotification(lang.get('messages.copied_to_clipboard'), opts);
    }
    if ($input === null && $temp !== null) {
        $temp.remove();
    }

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

/**
 *
 * @param {string} input
 * @param {string[]} allowedTags
 * @param {string[]} allowedDomains
 * @returns {string}
 */
function filterHTML(input, allowedTags, allowedDomains) {
    let tempDiv = document.createElement('div');
    tempDiv.innerHTML = input;

    let allElements = tempDiv.querySelectorAll('*');

    allElements.forEach(function (element) {
        let tagName = element.tagName.toLowerCase();

        if (!allowedTags.includes(tagName)) {
            element.replaceWith(document.createTextNode(element.innerText));
        } else {
            if (tagName === 'a' && element.hasAttribute('href')) {
                try {
                    let url = new URL(element.href);
                    if (!allowedDomains.includes(url.hostname)) {
                        element.replaceWith(document.createTextNode(element.innerText));
                        return;
                    }
                } catch (e) {
                    element.replaceWith(document.createTextNode(element.innerText));
                    return;
                }
            }

            // Remove all attributes except `href` for <a> tags that are allowed
            Array.from(element.attributes).forEach(attr => {
                if (!(tagName === 'a' && attr.name === 'href')) {
                    element.removeAttribute(attr.name);
                }
            });
        }
    });

    return tempDiv.innerHTML;
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

function getKillZonePaths() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE_PATH);
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

function getDungeonFloorSwitchMarkers() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);
}

function getUserMousePositions() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_USER_MOUSE_POSITION);
}

function getMountableAreas() {
    return getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MOUNTABLE_AREA);
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

function getDungeonFloorSwitchMarker(id) {
    return getDungeonFloorSwitchMarkers().findMapObjectById(id);
}

function getUserMousePosition(id) {
    return getUserMousePositions().findMapObjectById(id);
}

function getMountableArea(id) {
    return getMountableAreas().findMapObjectById(id);
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

/**
 * @see https://stackoverflow.com/a/1099670/771270
 * @returns {{}}
 */
function getQueryParams() {
    let qs = document.location.search.split('+').join(' '),
        params = {},
        tokens,
        re = /[?&]?([^=]+)=([^&]*)/g;

    let count = 0;
    while (tokens = re.exec(qs)) {
        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);

        // Just in case..
        if (++count > 100) {
            break;
        }
    }

    return params;
}
