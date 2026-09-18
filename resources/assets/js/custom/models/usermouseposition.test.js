// The initials shown for a connected user without an avatar must stay inside the marker circle at
// every zoom level: c.map.mapicon.calculateSize() doubles the marker across the zoom range, so the
// font size has to scale with it rather than by a fixed offset.
//
// Follows the global-script recipe from dungeonfloorswitchmarker.test.js: define the globals
// usermouseposition.js touches at load time, then require() it. The real Handlebars template is
// compiled here so the assertions cover the markup as it ships.
const fs = require('fs');
const path = require('path');
const Handlebars = require('handlebars');

global.L = {
    divIcon: (options) => options,
    Marker: {extend: () => class {}},
    Draw: {
        Marker: {extend: () => class {}},
        Feature: {prototype: {initialize() {}}},
    },
};
global.MapObject = class MapObject {
};
global.$.extend = Object.assign;

// Mirrors the helper registered in bootstrap.js, limited to the operator the template uses.
Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    return (v1 == v2) === (operator === '==') ? options.fn(this) : options.inverse(this);
});

global.Handlebars = {
    templates: {
        map_user_mouse_position_visual_template: Handlebars.compile(
            fs.readFileSync(
                path.join(__dirname, '../../handlebars/map_user_mouse_position_visual_template.handlebars'),
                'utf8'
            )
        ),
    },
};

const {c, MAP_OBJECT_GROUP_USER_MOUSE_POSITION} = require('../constants');

global.c = c;
global.MAP_OBJECT_GROUP_USER_MOUSE_POSITION = MAP_OBJECT_GROUP_USER_MOUSE_POSITION;

const {getUserMousePositionIcon} = require('./usermouseposition');

function renderAtZoomLevel(zoomLevel, userMousePosition = {}) {
    global.getState = () => ({
        getMapZoomLevel: () => zoomLevel,
    });

    return getUserMousePositionIcon($.extend({
        public_key: 'abcdef',
        initials: 'WW',
        color: '#ff0000',
        avatar_url: null,
    }, userMousePosition));
}

function getFontSize(icon) {
    const match = icon.html.match(/font-size: (\d+(?:\.\d+)?)px/);

    return match === null ? null : parseFloat(match[1]);
}

test('getUserMousePositionIcon_givenNoAvatar_returnsFontSizeThatFitsTheMarkerAtEveryZoomLevel', () => {
    // Arrange
    const zoomLevels = [0, 1, 2, 3, 4, 5];

    for (const zoomLevel of zoomLevels) {
        // Act
        const icon = renderAtZoomLevel(zoomLevel);

        // Assert - two initials at this font size are narrower than the circle they sit in
        const [width, height] = icon.iconSize;
        expect(getFontSize(icon)).toBeLessThanOrEqual(Math.min(width, height) / 2);
        expect(getFontSize(icon)).toBeGreaterThan(0);
    }
});

test('getUserMousePositionIcon_givenIncreasingZoomLevel_returnsFontSizeProportionalToTheMarker', () => {
    // Act
    const zoomedOut = renderAtZoomLevel(0);
    const zoomedIn = renderAtZoomLevel(5);

    // Assert
    expect(zoomedIn.iconSize[0]).toBe(zoomedOut.iconSize[0] * 2);
    // Rounded down to whole pixels, so the doubled font size is allowed to be a pixel short
    expect(getFontSize(zoomedIn)).toBeCloseTo(getFontSize(zoomedOut) * 2, -0.5);
});

test('getUserMousePositionIcon_givenNoAvatar_returnsInitialsInACentredContainer', () => {
    // Act
    const icon = renderAtZoomLevel(3, {initials: 'WW'});

    // Assert
    expect(icon.html).toContain('user_mouse_position_initials');
    expect(icon.html).toContain('WW');
    expect(icon.html).not.toContain('padding');
});

test('getUserMousePositionIcon_givenAvatar_returnsTheAvatarImage', () => {
    // Act
    const icon = renderAtZoomLevel(3, {avatar_url: 'https://example.com/avatar.png'});

    // Assert
    expect(icon.html).toContain('<img src="https://example.com/avatar.png"');
    expect(getFontSize(icon)).toBeNull();
});
