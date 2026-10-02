// hotkeys.js patches Leaflet's keyboard handler at load time, so real Leaflet replaces the setup.js stub first.
global.L = require('leaflet');
global.window.L = global.L;
require('leaflet-draw');

const jQuery = require('jquery');
globalThis.$ = jQuery;

const {Signalable} = require('./signalable');
globalThis.Signalable = Signalable;

const {Hotkeys} = require('./hotkeys');
globalThis.Hotkeys = Hotkeys;

globalThis.MapControl = class MapControl {
};
globalThis.DungeonMap = class DungeonMap {
};
globalThis.c = {
    map: {
        polyline: {defaultColor: () => '#ffffff', defaultWeight: 3},
        mountablearea: {color: 'green'},
        floorunionarea: {color: 'blue'},
    },
};

const {DrawControls} = require('./mapcontrols/drawcontrols');
globalThis.DrawControls = DrawControls;
const {AdminDrawControls} = require('./admin/admindrawcontrols');

/**
 * @param controlsClass {Function}
 * @returns {DrawTool[]}
 */
function getTools(controlsClass) {
    return Object.create(controlsClass.prototype)._getTools();
}

/**
 * @param key {String}
 * @param init {Object}
 * @returns {{key: String, shiftKey: Boolean, ctrlKey: Boolean, altKey: Boolean, metaKey: Boolean}}
 */
function keyEvent(key, init = {}) {
    return {key: key, shiftKey: false, ctrlKey: false, altKey: false, metaKey: false, ...init};
}

/**
 * @param tools {DrawTool[]}
 * @param key {String}
 * @param init {Object}
 * @returns {String|null}
 */
function toolIdFor(tools, key, init = {}) {
    return Hotkeys.findToolForKeyEvent(tools, keyEvent(key, init))?.id ?? null;
}

describe('Hotkeys chords', () => {
    test('parseChord_givenModifierChord_returnsKeyAndModifiers', () => {
        // Arrange
        const chord = 'Shift+U';

        // Act
        const parsed = Hotkeys.parseChord(chord);

        // Assert
        expect(parsed).toEqual({key: 'u', shift: true, ctrl: false, alt: false, meta: false});
    });

    test('formatChord_givenLetterAndModifierChords_returnsUppercaseLabels', () => {
        // Arrange, Act, Assert
        expect(Hotkeys.formatChord('1')).toBe('1');
        expect(Hotkeys.formatChord('p')).toBe('P');
        expect(Hotkeys.formatChord('shift+u')).toBe('Shift+U');
        expect(Hotkeys.formatChord('ctrl+alt+k')).toBe('Ctrl+Alt+K');
    });

    test('matchesChord_givenCapsLockLetter_returnsTrue', () => {
        // Arrange, Act, Assert
        expect(Hotkeys.matchesChord('p', keyEvent('P'))).toBe(true);
    });

    test('matchesChord_givenExtraOrMissingModifier_returnsFalse', () => {
        // Arrange, Act, Assert
        expect(Hotkeys.matchesChord('1', keyEvent('1'))).toBe(true);
        expect(Hotkeys.matchesChord('1', keyEvent('1', {ctrlKey: true}))).toBe(false);
        expect(Hotkeys.matchesChord('1', keyEvent('1', {altKey: true}))).toBe(false);
        expect(Hotkeys.matchesChord('1', keyEvent('1', {metaKey: true}))).toBe(false);
        expect(Hotkeys.matchesChord('p', keyEvent('P', {shiftKey: true}))).toBe(false);
        expect(Hotkeys.matchesChord('shift+u', keyEvent('U', {shiftKey: true}))).toBe(true);
        expect(Hotkeys.matchesChord('shift+u', keyEvent('u'))).toBe(false);
        expect(Hotkeys.matchesChord('ctrl+k', keyEvent('k', {ctrlKey: true}))).toBe(true);
    });
});

describe('Draw tool hotkeys per editor', () => {
    test('findToolForKeyEvent_givenRouteEditorKeys_returnsDesignedTools', () => {
        // Arrange
        const tools = getTools(DrawControls);

        // Act
        const resolved = ['1', 'p', '2', 'i', '3', 'b', '4', 'r', '5', 'e', '6', 'x']
            .map((key) => toolIdFor(tools, key));

        // Assert
        expect(resolved).toEqual([
            'path', 'path', 'mapicon', 'mapicon', 'brushline', 'brushline',
            'arrow', 'arrow', 'edit', 'edit', 'delete', 'delete',
        ]);
    });

    test('findToolForKeyEvent_givenAdminEditorKeys_returnsDesignedTools', () => {
        // Arrange
        const tools = getTools(AdminDrawControls);

        // Act
        const resolved = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 's', 'c']
            .map((key) => toolIdFor(tools, key));

        // Assert
        expect(resolved).toEqual([
            'mapicon', 'enemypack', 'enemy', 'enemypatrol', 'dungeonfloorswitchmarker', 'mountablearea',
            'floorunion', 'floorunionarea', 'edit', 'delete', 'dungeonstart', 'enemyforcescheckpoint',
        ]);
    });

    test('findToolForKeyEvent_givenAdminEditorRouteLetters_returnsNull', () => {
        // Arrange
        const tools = getTools(AdminDrawControls);

        // Act
        const resolved = ['p', 'i', 'b', 'r', 'e', 'x'].map((key) => toolIdFor(tools, key));

        // Assert
        expect(resolved).toEqual([null, null, null, null, null, null]);
    });

    test('findToolForKeyEvent_givenPullNavigationKeys_returnsNull', () => {
        // Arrange
        const tools = [...getTools(DrawControls), ...getTools(AdminDrawControls)];

        // Act
        const resolved = ['a', 'd', '[', ']'].map((key) => toolIdFor(tools, key));

        // Assert
        expect(resolved).toEqual([null, null, null, null]);
    });

    test.each([
        ['route editor', DrawControls],
        ['admin editor', AdminDrawControls],
    ])('getTools_given%s_hasUniqueIdsAndKeys', (name, controlsClass) => {
        // Arrange
        const tools = getTools(controlsClass);

        // Act
        const ids = tools.map((tool) => tool.id);
        const chords = tools.flatMap((tool) => tool.keys ?? []).map((chord) => Hotkeys.formatChord(chord));

        // Assert
        expect(ids.length).toBeGreaterThan(5);
        expect(new Set(ids).size).toBe(ids.length);
        expect(chords.length).toBeGreaterThan(5);
        expect(new Set(chords).size).toBe(chords.length);
    });
});

describe('Hotkeys key listener', () => {
    let previousGetState;
    let hotkeys;
    let map;
    let clicked;
    let setMapState;

    /**
     * @param target {Element}
     * @param key {String}
     * @param init {Object}
     * @returns {jQuery.Event}
     */
    function pressKey(target, key, init = {}) {
        const event = jQuery.Event('keydown', {key: key, ...init});
        jQuery(target).trigger(event);

        return event;
    }

    beforeEach(() => {
        previousGetState = globalThis.getState;
        setMapState = vi.fn();
        globalThis.getState = () => ({getDungeonMap: () => ({setMapState: setMapState})});

        document.body.innerHTML = '<div id="map"></div>' +
            '<a href="#" data-draw-tool="path"></a><a href="#" data-draw-tool="dungeonstart"></a>' +
            '<input id="input"><textarea id="notes"></textarea><div id="editable" contenteditable="true"></div>' +
            '<div id="modal" class="modal"></div>';

        clicked = [];
        document.querySelectorAll('[data-draw-tool]').forEach((button) => {
            button.addEventListener('click', () => clicked.push(button.dataset.drawTool));
        });

        map = {
            popupOpen: false,
            register: vi.fn(),
            hasPopupOpen() {
                return this.popupOpen;
            },
        };
        hotkeys = new Hotkeys(map);
        hotkeys.setTools([
            {id: 'path', keys: ['1', 'p']},
            {id: 'dungeonstart', keys: ['s']},
            {id: 'killzone', hidden: true},
        ]);
        hotkeys._mapRefreshed({});
    });

    afterEach(() => {
        jQuery(document).off('keydown.hotkeys');
        globalThis.getState = previousGetState;
        document.body.innerHTML = '';
    });

    test('onKeyPressed_givenKeyOutsideTheMap_clicksToolButton', () => {
        // Arrange
        const target = document.body;

        // Act
        const event = pressKey(target, 's');

        // Assert
        expect(clicked).toEqual(['dungeonstart']);
        expect(event.isDefaultPrevented()).toBe(true);
    });

    test('onKeyPressed_givenSecondKeyOfTool_clicksSameToolButton', () => {
        // Arrange, Act
        pressKey(document.getElementById('map'), '1');
        pressKey(document.getElementById('map'), 'p');

        // Assert
        expect(clicked).toEqual(['path', 'path']);
    });

    test('onKeyPressed_givenModifierHeld_ignoresKey', () => {
        // Arrange
        const map = document.getElementById('map');

        // Act
        pressKey(map, '1', {ctrlKey: true});
        pressKey(map, '1', {altKey: true});
        pressKey(map, '1', {metaKey: true});
        pressKey(map, '1');

        // Assert
        expect(clicked).toEqual(['path']);
    });

    test.each([
        ['input'],
        ['notes'],
        ['editable'],
    ])('onKeyPressed_givenTypingIn%s_ignoresKey', (id) => {
        // Arrange
        const target = document.getElementById(id);

        // Act
        pressKey(target, '1');
        pressKey(target, 'Escape');
        pressKey(document.body, '1');

        // Assert
        expect(clicked).toEqual(['path']);
        expect(setMapState).not.toHaveBeenCalled();
    });

    test('onKeyPressed_givenPopupOpen_ignoresKey', () => {
        // Arrange
        map.popupOpen = true;

        // Act
        pressKey(document.body, '1');
        map.popupOpen = false;
        pressKey(document.body, '1');

        // Assert
        expect(clicked).toEqual(['path']);
    });

    test('onKeyPressed_givenModalShown_ignoresKey', () => {
        // Arrange
        document.getElementById('modal').classList.add('show');

        // Act
        pressKey(document.body, '1');
        document.getElementById('modal').classList.remove('show');
        pressKey(document.body, '1');

        // Assert
        expect(clicked).toEqual(['path']);
    });

    test('onKeyPressed_givenEscape_clearsMapState', () => {
        // Arrange, Act
        pressKey(document.body, 'Escape');

        // Assert
        expect(setMapState).toHaveBeenCalledWith(null);
        expect(clicked).toEqual([]);
    });
});

describe('Leaflet keyboard zoom', () => {
    test('keyboard_givenSixKey_doesNotZoomOut', () => {
        // Arrange
        const container = document.createElement('div');
        document.body.appendChild(container);

        // Act
        const map = L.map(container, {center: [0, 0], zoom: 2});

        // Assert
        expect(map.keyboard._zoomKeys[54]).toBeUndefined();
        expect(map.keyboard._zoomKeys[189]).toBe(-1);

        map.remove();
        document.body.innerHTML = '';
    });
});
