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
        expect(localMapObject.layer.bindTooltip).toHaveBeenCalledWith('Wotuu', expect.objectContaining({permanent: true}));
    });
});
