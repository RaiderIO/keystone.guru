// MapObjectGroup._showReceivedFromEcho() labels the changed map object with the name of the user who
// changed it. Leaflet renders string tooltip content as HTML, and that name is another user's text.

const {Signalable} = require('../signalable');
globalThis.Signalable = Signalable;
globalThis.KillZone = class KillZone {
};
globalThis.Handlebars = require('handlebars');
globalThis.isColorDark = () => true;

const {MapObjectGroup} = require('./mapobjectgroup');

describe('MapObjectGroup._showReceivedFromEcho', () => {
    let originalGetState;
    let originalC;

    beforeEach(() => {
        vi.useFakeTimers();
        originalGetState = globalThis.getState;
        originalC = globalThis.c;

        globalThis.getState = () => ({
            isEchoEnabled: () => true,
            getUser: () => ({public_key: 'local-user'}),
        });
        globalThis.c = {map: {echo: {tooltipFadeOutTimeoutMs: 1000}}};
    });

    afterEach(() => {
        vi.useRealTimers();
        globalThis.getState = originalGetState;
        globalThis.c = originalC;
    });

    /**
     * @returns {{layer: Object}}
     */
    function buildLocalMapObject() {
        return {
            layer: {
                _leaflet_id: 1,
                getTooltip: () => undefined,
                bindTooltip: vi.fn(() => ({closeTooltip: vi.fn()})),
            },
        };
    }

    /**
     * A map object whose layer already carries a tooltip of its own (a pull's index, say).
     *
     * @returns {{localMapObject: {layer: Object}, oldTooltip: Object, echoTooltip: {closeTooltip: Function}}}
     */
    function buildLocalMapObjectWithTooltip() {
        const oldTooltip = {_content: '3', options: {className: 'killzone_index_tooltip'}};
        const echoTooltip = {closeTooltip: vi.fn()};
        const localMapObject = {
            layer: {
                _leaflet_id: 1,
                getTooltip: () => oldTooltip,
                bindTooltip: vi.fn(() => echoTooltip),
            },
        };

        return {localMapObject, oldTooltip, echoTooltip};
    }

    it('_showReceivedFromEcho_givenUserNameContainingMarkup_bindsTheNameEscaped', () => {
        // Arrange
        const group = Object.create(MapObjectGroup.prototype);
        const localMapObject = buildLocalMapObject();
        const user = {public_key: 'remote-user', color: '#000000', name: '<img src=x onerror=alert(1)>'};

        // Act
        group._showReceivedFromEcho(localMapObject, user);

        // Assert
        const content = localMapObject.layer.bindTooltip.mock.calls[0][0];
        const tooltip = document.createElement('div');
        tooltip.innerHTML = content;
        expect(tooltip.children.length).toBe(0);
        expect(tooltip.textContent).toBe(user.name);
    });

    it('_showReceivedFromEcho_givenPlainUserName_bindsTheNameUnchanged', () => {
        // Arrange
        const group = Object.create(MapObjectGroup.prototype);
        const localMapObject = buildLocalMapObject();

        // Act
        group._showReceivedFromEcho(localMapObject, {public_key: 'remote-user', color: '#000000', name: 'Wotuu'});

        // Assert
        expect(localMapObject.layer.bindTooltip).toHaveBeenCalledWith('Wotuu', {
            permanent: true,
            className: 'echo_user_remote-user_tooltip text-white',
            direction: 'top',
        });
    });

    it('_showReceivedFromEcho_givenTheLocalUsersOwnChange_bindsNoTooltip', () => {
        // Arrange
        const group = Object.create(MapObjectGroup.prototype);
        const localMapObject = buildLocalMapObject();

        // Act
        group._showReceivedFromEcho(localMapObject, {public_key: 'local-user', color: '#000000', name: 'Me'});

        // Assert
        expect(localMapObject.layer.bindTooltip).not.toHaveBeenCalled();
    });

    it('_showReceivedFromEcho_givenEchoDisabled_bindsNoTooltip', () => {
        // Arrange
        globalThis.getState = () => ({
            isEchoEnabled: () => false,
            getUser: () => ({public_key: 'local-user'}),
        });
        const group = Object.create(MapObjectGroup.prototype);
        const localMapObject = buildLocalMapObject();

        // Act
        group._showReceivedFromEcho(localMapObject, {public_key: 'remote-user', color: '#000000', name: 'Wotuu'});

        // Assert
        expect(localMapObject.layer.bindTooltip).not.toHaveBeenCalled();
    });

    it('_showReceivedFromEcho_givenFadeOutTimeoutElapsed_closesTheNameAndRebindsTheOldTooltip', () => {
        // Arrange
        const group = Object.create(MapObjectGroup.prototype);
        const {localMapObject, oldTooltip, echoTooltip} = buildLocalMapObjectWithTooltip();
        group._showReceivedFromEcho(localMapObject, {public_key: 'remote-user', color: '#000000', name: 'Wotuu'});

        // Act
        vi.advanceTimersByTime(999);
        const closedBeforeTimeout = echoTooltip.closeTooltip.mock.calls.length;
        vi.advanceTimersByTime(1);

        // Assert
        expect(closedBeforeTimeout).toBe(0);
        expect(echoTooltip.closeTooltip).toHaveBeenCalledOnce();
        expect(localMapObject.layer.bindTooltip).toHaveBeenCalledTimes(2);
        expect(localMapObject.layer.bindTooltip).toHaveBeenLastCalledWith('3', oldTooltip.options);
    });

    it('_showReceivedFromEcho_givenLayerReplacedBeforeFadeOut_doesNotRebindTheOldTooltip', () => {
        // Arrange
        const group = Object.create(MapObjectGroup.prototype);
        const {localMapObject, echoTooltip} = buildLocalMapObjectWithTooltip();
        group._showReceivedFromEcho(localMapObject, {public_key: 'remote-user', color: '#000000', name: 'Wotuu'});
        const replacementLayer = {_leaflet_id: 2, bindTooltip: vi.fn()};
        localMapObject.layer = replacementLayer;

        // Act
        vi.advanceTimersByTime(1000);

        // Assert
        expect(echoTooltip.closeTooltip).toHaveBeenCalledOnce();
        expect(replacementLayer.bindTooltip).not.toHaveBeenCalled();
    });
});
