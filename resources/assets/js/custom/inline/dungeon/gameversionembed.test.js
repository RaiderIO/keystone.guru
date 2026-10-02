const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

const {DungeonExploreGameversionEmbed} = require('./explore/gameversion/embed');
const {DungeonHeatmapGameversionEmbed} = require('./heatmap/gameversion/embed');

/**
 * Activates the embed and returns the message listener it registered on the window.
 * @param {Function} embedClass
 * @returns {Function}
 */
function activateAndCaptureMessageListener(embedClass) {
    const addEventListener = vi.spyOn(window, 'addEventListener').mockImplementation(() => {
    });

    new embedClass('id', 'dungeon/embed', {}).activate();

    const call = addEventListener.mock.calls.find(([type]) => type === 'message');
    return call[1];
}

/**
 * @param {Object} data
 * @returns {{origin: string, data: Object}}
 */
function buildSetFiltersEvent(data = {}) {
    return {origin: 'http://localhost:8008', data: {function: 'setFilters', ...data}};
}

describe.each([
    ['DungeonExploreGameversionEmbed', DungeonExploreGameversionEmbed],
    ['DungeonHeatmapGameversionEmbed', DungeonHeatmapGameversionEmbed],
])('%s setFilters message', (name, embedClass) => {
    afterEach(() => {
        delete globalThis._inlineManager;
    });

    it('onMessage_givenNoHeatmapSearchSidebar_logsErrorWithoutThrowing', () => {
        // Arrange
        globalThis._inlineManager = {getInlineCode: (bladePath) => bladePath === 'common/maps/heatmapsearchsidebar' ? [] : false};
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {
        });
        const listener = activateAndCaptureMessageListener(embedClass);

        // Act
        let result;
        const act = () => {
            result = listener(buildSetFiltersEvent({includeSpecIds: '250'}));
        };

        // Assert
        expect(act).not.toThrow();
        expect(result).toBe(false);
        expect(consoleError).toHaveBeenCalledWith('Unable to find sidebar!');
    });

    it('onMessage_givenHeatmapSearchSidebar_appliesFiltersWithoutFunctionKey', () => {
        // Arrange
        const sidebar = new InlineCode('sidebar', 'common/maps/heatmapsearchsidebar', {});
        sidebar.searchWithFilters = vi.fn();
        globalThis._inlineManager = {getInlineCode: (bladePath) => bladePath === 'common/maps/heatmapsearchsidebar' ? sidebar : []};
        vi.spyOn(console, 'log').mockImplementation(() => {
        });
        const listener = activateAndCaptureMessageListener(embedClass);

        // Act
        const result = listener(buildSetFiltersEvent({includeSpecIds: '250,251'}));

        // Assert
        expect(result).toBe(true);
        expect(sidebar.searchWithFilters).toHaveBeenCalledWith({includeSpecIds: '250,251'});
    });
});
