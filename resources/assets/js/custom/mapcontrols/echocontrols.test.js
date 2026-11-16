// A connected user's color arrives from the server (presence channel and user-color-changed
// payloads) and is written into a <style> element and into the EchoUser that templates render.
// Only #rgb / #rrggbb colors may reach those sinks; anything else is replaced by the fallback.

const $ = require('jquery');
const {sanitizeHexColor, isHexColor, isColorDark} = require('../colorutil');
const {Signalable} = require('../signalable');

globalThis.$ = $;
globalThis.sanitizeHexColor = sanitizeHexColor;
globalThis.isHexColor = isHexColor;
// colorutil's isColorDark() throws on a non-hex color; tolerate one so a regression surfaces as a failed assertion
globalThis.isColorDark = (hex) => isHexColor(hex) && isColorDark(hex);
globalThis.Signalable = Signalable;
globalThis.MapControl = class MapControl {
};

const EchoControls = require('./echocontrols');
const EchoHandler = require('../echo/echohandler');
const EchoUser = require('../echo/echouser');

const FALLBACK_COLOR = '#000';
const MARKUP_COLOR = 'red;}</style><script>window.injected = true;</script><img src="x">';

describe('EchoControls._applyUserColor', () => {
    let originalGetState;

    function applyUserColorWithRawColor(rawColor) {
        globalThis.getState = () => ({
            getEchoHandler: () => ({getUserColor: () => rawColor}),
        });

        Object.create(EchoControls.prototype)._applyUserColor({public_key: 'abc123', color: rawColor});

        return document.getElementById('style_echo_user_abc123');
    }

    beforeEach(() => {
        originalGetState = globalThis.getState;
    });

    afterEach(() => {
        globalThis.getState = originalGetState;
        document.head.innerHTML = '';
    });

    it('_applyUserColor_givenNonHexColor_appliesFallbackColor', () => {
        // Arrange
        const rawColor = 'red';

        // Act
        const style = applyUserColorWithRawColor(rawColor);

        // Assert
        expect(style.textContent).toContain(`border: 3px ${FALLBACK_COLOR} solid`);
        expect(style.textContent).toContain(`background-color: ${FALLBACK_COLOR};`);
        expect(style.textContent).not.toContain('red');
    });

    it('_applyUserColor_givenHexColor_appliesThatColor', () => {
        // Arrange
        const rawColor = '#12ab9F';

        // Act
        const style = applyUserColorWithRawColor(rawColor);

        // Assert
        expect(style.textContent).toContain('border: 3px #12ab9F solid');
        expect(style.textContent).toContain('background-color: #12ab9F;');
    });

    it('_applyUserColor_givenMarkupInColor_writesNoMarkupIntoStyle', () => {
        // Arrange
        const rawColor = MARKUP_COLOR;

        // Act
        const style = applyUserColorWithRawColor(rawColor);

        // Assert
        expect(style.children.length).toBe(0);
        expect(style.textContent).not.toContain('<');
        expect(style.textContent).toContain(`background-color: ${FALLBACK_COLOR};`);
        expect(window.injected).toBeUndefined();
    });

    it('_applyUserColor_givenColor_writesStyleAsTextNotHtml', () => {
        // Arrange
        const htmlSpy = vi.spyOn($.fn, 'html');
        const textSpy = vi.spyOn($.fn, 'text');

        // Act
        const style = applyUserColorWithRawColor('#ffffff');

        // Assert
        expect(htmlSpy).not.toHaveBeenCalled();
        expect(textSpy).toHaveBeenCalled();
        expect(style.textContent).toContain('background-color: #ffffff;');
    });
});

describe('EchoHandler.getUserColor', () => {
    function createHandlerWithUserColor(color) {
        const handler = Object.create(EchoHandler.prototype);
        handler._echoUsers = [{getPublicKey: () => 'abc123', getColor: () => color}];

        return handler;
    }

    it('getUserColor_givenNonHexColor_returnsFallbackColor', () => {
        // Arrange
        const handler = createHandlerWithUserColor(MARKUP_COLOR);

        // Act
        const result = handler.getUserColor('abc123');

        // Assert
        expect(result).toBe(FALLBACK_COLOR);
    });

    it('getUserColor_givenUnknownUser_returnsFallbackColor', () => {
        // Arrange
        const handler = createHandlerWithUserColor('#ff0000');

        // Act
        const result = handler.getUserColor('unknown');

        // Assert
        expect(result).toBe(FALLBACK_COLOR);
    });

    it('getUserColor_givenHexColor_returnsThatColor', () => {
        // Arrange
        const handler = createHandlerWithUserColor('#ff0000');

        // Act
        const result = handler.getUserColor('abc123');

        // Assert
        expect(result).toBe('#ff0000');
    });
});

describe('EchoUser color', () => {
    let originalGetState;

    function createEchoUser(color) {
        const map = {
            mapObjectGroupManager: {
                getUserMousePositionMapObjectGroup: () => ({createNewUserMousePosition: () => ({})}),
            },
        };

        return new EchoUser(map, {public_key: 'abc123', name: 'Wotuu', initials: 'WW', color: color});
    }

    beforeEach(() => {
        originalGetState = globalThis.getState;
        globalThis.getState = () => ({getUser: () => ({public_key: 'self'})});
    });

    afterEach(() => {
        globalThis.getState = originalGetState;
    });

    it('constructor_givenNonHexColor_storesFallbackColor', () => {
        // Arrange
        const color = MARKUP_COLOR;

        // Act
        const echoUser = createEchoUser(color);

        // Assert
        expect(echoUser.getColor()).toBe(FALLBACK_COLOR);
        expect(echoUser.color).toBe(FALLBACK_COLOR);
    });

    it('constructor_givenHexColor_storesThatColor', () => {
        // Arrange
        const color = '#abc';

        // Act
        const echoUser = createEchoUser(color);

        // Assert
        expect(echoUser.getColor()).toBe('#abc');
    });

    it('setColor_givenNonHexColor_storesFallbackColor', () => {
        // Arrange
        const echoUser = createEchoUser('#abcdef');

        // Act
        echoUser.setColor('url(https://example.com/x.png)');

        // Assert
        expect(echoUser.getColor()).toBe(FALLBACK_COLOR);
    });
});
