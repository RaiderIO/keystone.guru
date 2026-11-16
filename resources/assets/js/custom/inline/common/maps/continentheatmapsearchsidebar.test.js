// ---------------------------------------------------------------------------
// A continent's heatmap sidebar offers the open-world filter set (death type, region, item level, character level
// and week), a dungeon's the Mythic+ set. The sidebar is driven for real - real jQuery and the real
// SearchInlineBase/SearchInlineHeatmapBase/SearchParams/SearchFilter* classes of the open-world set - mirroring
// heatmapsearchsidebar.urlrestore.test.js. The contract asserted is the request the sidebar sends.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {
    COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
    COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH,
    COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL,
    USER_ROLE_ADMIN,
    USER_ROLE_INTERNAL_TEAM,
} = require('../../../constants');
Object.assign(globalThis, {
    COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
    COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH,
    COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL,
    USER_ROLE_ADMIN,
    USER_ROLE_INTERNAL_TEAM,
    cookieDefaultAttributes: {},
});

const {getQueryParams} = require('../../../util');
globalThis.getQueryParams = getQueryParams;

const {refreshSelectPickers} = require('../../../../selectpicker');
globalThis.refreshSelectPickers = refreshSelectPickers;

const MESSAGES = {
    'js.filter_input_death_type_header': 'Death type: :value',
    'js.filter_input_select_weeks_header': 'Weeks: :from - :to',
    'js.filter_input_character_level_header': 'Character level: :value',
    'combatlogeventtypes.player_death': 'Player death',
};
globalThis.lang = {get: (key) => MESSAGES[key] ?? key};

// Filter class hierarchy - each file expects its parent as a bare global, so require them parent first.
const {SearchFilter} = require('../../common/search/filters/filter');
globalThis.SearchFilter = SearchFilter;
const {SearchFilterInput} = require('../../common/search/filters/filterinput');
globalThis.SearchFilterInput = SearchFilterInput;
const {SearchFilterRadio} = require('../../common/search/filters/filterradio');
globalThis.SearchFilterRadio = SearchFilterRadio;
globalThis.SearchFilterRadioEventType = require('../../common/search/filters/filterradioeventtype').SearchFilterRadioEventType;
globalThis.SearchFilterRadioDeathType = require('../../common/search/filters/filterradiodeathtype').SearchFilterRadioDeathType;
globalThis.SearchFilterRadioRegion = require('../../common/search/filters/filterradioregion').SearchFilterRadioRegion;
globalThis.SearchFilterItemLevel = require('../../common/search/filters/filterinputitemlevel').SearchFilterItemLevel;
globalThis.SearchFilterCharacterLevel = require('../../common/search/filters/filterinputcharacterlevel').SearchFilterCharacterLevel;
globalThis.SearchFilterWeeklyAffixGroups = require('../../common/search/filters/filterinputweeklyaffixgroups').SearchFilterWeeklyAffixGroups;
globalThis.SearchFilterWeeks = require('../../common/search/filters/filterinputweeks').SearchFilterWeeks;
globalThis.SearchFilterPassThrough = require('../../common/search/filters/filterpassthrough').SearchFilterPassThrough;

globalThis.SearchParams = require('../../common/search/searchparams').SearchParams;
const {SearchInlineBase} = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;

// The Mythic+ filters only need to exist: an always-empty value keeps them out of the request.
class StubFilter extends SearchFilter {
    getValue() {
        return '';
    }
}

for (const name of [
    'SearchFilterRadioDataType',
    'SearchFilterPlayerSpells',
    'SearchFilterMythicLevel',
    'SearchFilterPlayerDeaths',
    'SearchFilterAffixes',
    'SearchFilterClasses',
    'SearchFilterSpecializations',
    'SearchFilterClassesPlayerDeaths',
    'SearchFilterSpecializationsPlayerDeaths',
    'SearchFilterDuration',
]) {
    globalThis[name] = StubFilter;
}

for (const name of [
    'HeatOptionMinOpacityHandler',
    'HeatOptionMaxZoomHandler',
    'HeatOptionMaxHandler',
    'HeatOptionRadiusHandler',
    'HeatOptionBlurHandler',
]) {
    globalThis[name] = class {
        apply() {
        }
    };
}

/** The onFinish the item level slider was set up with, so a test can play the user releasing the slider */
let itemLevelOnFinish = null;
/** The arguments the item level slider was constructed with: from, to, and the slider's own min and max */
let itemLevelHandlerArguments = null;
globalThis.ItemLevelHandler = class {
    constructor(...args) {
        itemLevelHandlerArguments = args;
    }

    apply(selector, options) {
        itemLevelOnFinish = options.onFinish;
    }
};

/** The onFinish the character level slider was set up with */
let characterLevelOnFinish = null;
/** The arguments the character level slider was constructed with: its min and max */
let characterLevelHandlerArguments = null;
globalThis.CharacterLevelHandler = class {
    constructor(...args) {
        characterLevelHandlerArguments = args;
    }

    apply(selector, options) {
        characterLevelOnFinish = options.onFinish;
    }
};

globalThis.Sidebar = class {
    activate() {
    }

    showSidebar() {
    }
};

const searchSpy = vi.fn();
globalThis.SearchHandlerHeatmap = class {
    search(searchParams, options) {
        searchSpy(searchParams, options);
    }
};

globalThis.Cookies = {get: () => undefined, set: () => undefined};

const DUNGEON_ID = 42;
const ITEM_LEVEL_MIN = 1;
const ITEM_LEVEL_MAX = 400;
const CHARACTER_LEVEL_MIN = 1;
const CHARACTER_LEVEL_MAX = 90;
// What MapContextDungeonExplore::getSeasonWeeks() hands a continent: the most recent weeks, oldest first
const CONTINENT_WEEKS = [
    {week: 1, period: 1087, start: '2026-10-27'},
    {week: 2, period: 1088, start: '2026-11-03'},
    {week: 3, period: 1089, start: '2026-11-10'},
];

globalThis.getState = () => ({
    userHasRole: () => false,
    getDungeonMap: () => ({
        pluginHeat: {
            toggle: () => undefined,
            setRawLatLngsPerFloor: () => undefined,
        },
    }),
    getMapContext: () => ({
        getDungeon: () => ({id: DUNGEON_ID}),
        getSeasonWeeks: () => CONTINENT_WEEKS,
    }),
});

globalThis.SearchInlineHeatmapBase = require('../../base/searchinlineheatmapbase').SearchInlineHeatmapBase;
const {CommonMapsContinentheatmapsearchsidebar} = require('./continentheatmapsearchsidebar');
const {CommonMapsHeatmapsearchsidebar} = require('./heatmapsearchsidebar');

const OPTIONS = {
    defaultState: 0,
    enabledStateCookie: 'heatmap_search_enabled',
    enabledStateSelector: '#heatmap_search_toggle',
    filterEventTypeContainerSelector: '#filter_event_type_container',
    filterEventTypeSelector: 'input[name="event_type"]',
    filterRegionContainerSelector: '#filter_region_container',
    filterRegionSelector: 'input[name="region"]',
    filterItemLevelSelector: '#filter_item_level',
    filterCharacterLevelSelector: '#filter_character_level',
    filterWeeksSelector: '#filter_weeks',
    itemLevelMin: ITEM_LEVEL_MIN,
    itemLevelMax: ITEM_LEVEL_MAX,
    characterLevelMin: CHARACTER_LEVEL_MIN,
    characterLevelMax: CHARACTER_LEVEL_MAX,
};

/**
 * Builds the filters of common/maps/controls/continentheatmapsearch.blade.php.
 */
function buildContinentSidebarDom() {
    document.body.innerHTML = `
        <div id="map"></div>
        <input type="checkbox" id="heatmap_search_toggle" checked>

        <div id="filter_event_type_container">
            <input type="radio" name="event_type" class="btn-check ${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}" value="${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}" checked>
            <input type="radio" name="event_type" class="btn-check ${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}" value="${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}">
        </div>

        <div id="filter_region_container">
            <input type="radio" name="region" class="btn-check world" value="world" checked>
            <input type="radio" name="region" class="btn-check eu" value="eu">
        </div>

        <input type="text" id="filter_item_level" value="${ITEM_LEVEL_MIN};${ITEM_LEVEL_MAX}">

        <input type="text" id="filter_character_level" value="${CHARACTER_LEVEL_MIN};${CHARACTER_LEVEL_MAX}">

        <select id="filter_weeks" multiple>
            ${CONTINENT_WEEKS.map(({week, start}) => `<option value="${week}">Week of ${start}</option>`).join('')}
        </select>
    `;

    // Stands in for the ion-range-slider instances ItemLevelHandler and CharacterLevelHandler attach
    for (const selector of ['#filter_item_level', '#filter_character_level']) {
        const $slider = $(selector);
        $slider.data('rangeSlider', {
            update: ({from, to}) => $slider.val(`${from};${to}`),
        });
    }
}

/**
 * @param {String} queryString
 * @param {Object} optionOverrides
 * @returns {CommonMapsContinentheatmapsearchsidebar}
 */
function activateContinentSidebar(queryString = '', optionOverrides = {}) {
    buildContinentSidebarDom();
    window.history.replaceState({}, '', `/heatmap/forever/kalimdor/1${queryString}`);

    const sidebar = new CommonMapsContinentheatmapsearchsidebar(
        'continentheatmapsearchsidebar',
        'common/maps/continentheatmapsearchsidebar',
        $.extend({}, OPTIONS, optionOverrides)
    );
    sidebar.activate();

    return sidebar;
}

/**
 * @returns {Object} The params of the most recent request.
 */
function lastRequestParams() {
    return searchSpy.mock.calls[searchSpy.mock.calls.length - 1][0].params;
}

describe('CommonMapsContinentheatmapsearchsidebar - the open-world filter set', () => {
    beforeEach(() => {
        searchSpy.mockClear();
        itemLevelOnFinish = null;
        itemLevelHandlerArguments = null;
        characterLevelOnFinish = null;
        characterLevelHandlerArguments = null;
    });

    test('activate_givenContinent_offersTheOpenWorldItemLevelRangeOnTheSlider', () => {
        // Arrange & Act
        activateContinentSidebar();

        // Assert - the slider's own Mythic+ range would clamp an open-world default to its 240 minimum
        expect(itemLevelHandlerArguments).toEqual([ITEM_LEVEL_MIN, ITEM_LEVEL_MAX, ITEM_LEVEL_MIN, ITEM_LEVEL_MAX]);
    });

    test('activate_givenDungeon_keepsTheSlidersOwnItemLevelRange', () => {
        // Arrange
        buildContinentSidebarDom();
        const sidebar = new CommonMapsHeatmapsearchsidebar('heatmapsearchsidebar', 'common/maps/heatmapsearchsidebar',
            $.extend({}, OPTIONS));

        // Act
        sidebar.filters['itemLevel'].activate();

        // Assert
        expect(itemLevelHandlerArguments).toEqual([ITEM_LEVEL_MIN, ITEM_LEVEL_MAX]);
    });

    test('constructor_givenContinent_createsOnlyTheOpenWorldFilters', () => {
        // Arrange
        buildContinentSidebarDom();

        // Act
        const sidebar = new CommonMapsContinentheatmapsearchsidebar('continentheatmapsearchsidebar',
            'common/maps/continentheatmapsearchsidebar', $.extend({}, OPTIONS));

        // Assert
        expect(Object.keys(sidebar.filters)).toEqual(['type', 'region', 'itemLevel', 'characterLevel', 'weeks', 'showSidebar', 'token']);
    });

    test('constructor_givenDungeon_createsTheMythicPlusFilters', () => {
        // Arrange
        buildContinentSidebarDom();

        // Act
        const sidebar = new CommonMapsHeatmapsearchsidebar('heatmapsearchsidebar', 'common/maps/heatmapsearchsidebar',
            $.extend({}, OPTIONS));

        // Assert
        expect(Object.keys(sidebar.filters)).toEqual(expect.arrayContaining([
            'type', 'dataType', 'includePlayerSpellIds', 'region', 'keyLevel', 'itemLevel', 'playerDeaths',
            'includeAffixIds', 'weeklyAffixGroups', 'includeClassIds', 'includeSpecIds', 'includePlayerDeathClassIds',
            'includePlayerDeathSpecIds', 'duration', 'season',
        ]));
        expect(sidebar.filters).not.toHaveProperty('weeks');
        expect(sidebar.filters).not.toHaveProperty('characterLevel');
    });

    test('activate_givenDungeonLinkWithACharacterLevelBand_neverRequestsTheCharacterLevelBand', () => {
        // Arrange
        buildContinentSidebarDom();
        $('body').append('<select id="filter_weekly_affix_groups" multiple></select>');
        window.history.replaceState({}, '', '/heatmap/retail/the-stonevault/1?minLevel=10&maxLevel=33&region=eu');
        const sidebar = new CommonMapsHeatmapsearchsidebar('heatmapsearchsidebar', 'common/maps/heatmapsearchsidebar',
            $.extend({}, OPTIONS, {filterWeeklyAffixGroupsSelector: '#filter_weekly_affix_groups'}));

        // Act
        sidebar.activate();

        // Assert - region is the positive control: the link was read, only the level band was not
        expect(lastRequestParams().region).toBe('eu');
        expect(lastRequestParams()).not.toHaveProperty('minLevel');
        expect(lastRequestParams()).not.toHaveProperty('maxLevel');
    });

    test('activate_givenContinent_offersTheCharacterLevelRangeOnTheSlider', () => {
        // Arrange & Act
        activateContinentSidebar();

        // Assert
        expect(characterLevelHandlerArguments).toEqual([CHARACTER_LEVEL_MIN, CHARACTER_LEVEL_MAX]);
    });

    test('changeCharacterLevel_givenContinent_requestsTheCharacterLevelBand', () => {
        // Arrange
        activateContinentSidebar();

        // Act - the user releases the slider at 10 - 33
        $('#filter_character_level').val('10;33');
        characterLevelOnFinish();

        // Assert
        expect(lastRequestParams()).toMatchObject({minLevel: 10, maxLevel: 33});
    });

    test('activate_givenContinent_searchesForPlayerDeathsWorldwide', () => {
        // Arrange & Act
        activateContinentSidebar();

        // Assert - untouched item level and week filters stay out of the request
        expect(searchSpy).toHaveBeenCalledTimes(1);
        expect(lastRequestParams()).toEqual({
            dungeonId: DUNGEON_ID,
            type: COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH,
            region: 'world',
        });
    });

    test('changeDeathType_givenContinent_requestsTheOtherDeathType', () => {
        // Arrange
        activateContinentSidebar();

        // Act
        $(`input[name="event_type"].${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}`).prop('checked', true).trigger('change');

        // Assert
        expect(lastRequestParams().type).toBe(COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH);
    });

    test('changeRegion_givenContinent_requestsTheRegion', () => {
        // Arrange
        activateContinentSidebar();

        // Act
        $('input[name="region"].eu').prop('checked', true).trigger('change');

        // Assert
        expect(lastRequestParams().region).toBe('eu');
    });

    test('changeItemLevel_givenContinent_requestsTheItemLevelRange', () => {
        // Arrange
        activateContinentSidebar();

        // Act - the user releases the slider at 100 - 300
        $('#filter_item_level').val('100;300');
        itemLevelOnFinish();

        // Assert
        expect(lastRequestParams()).toMatchObject({minItemLevel: 100, maxItemLevel: 300});
    });

    test('changeWeeks_givenContinent_requestsThePeriodsOfEveryWeekInBetween', () => {
        // Arrange
        activateContinentSidebar();

        // Act - the user picks the first and the last week
        $('#filter_weeks').val(['1', '3']).trigger('change');

        // Assert
        expect($('#filter_weeks').val()).toEqual(['1', '2', '3']);
        expect(lastRequestParams()).toMatchObject({minPeriod: 1087, maxPeriod: 1089});
    });

    test('activate_givenContinentLinkWithMythicPlusParams_requestsOnlyTheOpenWorldFilters', () => {
        // Arrange & Act
        activateContinentSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}&region=eu&minItemLevel=100&maxItemLevel=300` +
            '&minLevel=10&maxLevel=33&minPeriod=1088&maxPeriod=1089&minMythicLevel=10&includeClassIds=1&season=season-mn-1');

        // Assert
        expect(lastRequestParams()).toEqual({
            dungeonId: DUNGEON_ID,
            type: COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
            region: 'eu',
            minItemLevel: 100,
            maxItemLevel: 300,
            minLevel: 10,
            maxLevel: 33,
            minPeriod: 1088,
            maxPeriod: 1089,
        });
    });

    test('activate_givenContinentLink_writesTheOpenWorldFiltersBackToTheUrl', () => {
        // Arrange
        const queryString = `?type=${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}&region=eu&minItemLevel=100&maxItemLevel=300&minLevel=10&maxLevel=33&minPeriod=1088&maxPeriod=1089`;

        // Act
        activateContinentSidebar(queryString);

        // Assert - a shared continent link round trips
        expect(Object.fromEntries(new URLSearchParams(window.location.search)))
            .toEqual(Object.fromEntries(new URLSearchParams(queryString)));
    });

    test('searchWithFilters_givenSidebarlessContinentEmbed_requestsOnlyTheOpenWorldFilters', () => {
        // Arrange - a sidebar-less embed is driven entirely through postMessage
        const sidebar = activateContinentSidebar('', {passThroughEverything: true});
        searchSpy.mockClear();

        // Act
        sidebar.searchWithFilters({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
            region: 'eu',
            minItemLevel: 100,
            maxItemLevel: 300,
            minLevel: 10,
            maxLevel: 33,
            minPeriod: 1088,
            maxPeriod: 1089,
            minMythicLevel: 10,
            includeClassIds: '1',
        });

        // Assert
        expect(lastRequestParams()).toEqual({
            dungeonId: DUNGEON_ID,
            type: COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
            region: 'eu',
            minItemLevel: 100,
            maxItemLevel: 300,
            minLevel: 10,
            maxLevel: 33,
            minPeriod: 1088,
            maxPeriod: 1089,
        });
    });
});

describe('The open-world filter headers', () => {
    test('getFilterHeaderText_givenSelectedWeeks_namesTheWeeksByTheirStartDate', () => {
        // Arrange
        const filter = new SearchFilterWeeks(null, () => undefined, {passThrough: true});
        filter.setValue([1, 2]);

        // Act
        const result = filter.getFilterHeaderText();

        // Assert
        expect(result).toBe('Weeks: 2026-10-27 - 2026-11-03');
    });

    test('getFilterHeaderText_givenCharacterLevelBand_namesTheCharacterLevelBand', () => {
        // Arrange
        const filter = new SearchFilterCharacterLevel(null, () => undefined, CHARACTER_LEVEL_MIN, CHARACTER_LEVEL_MAX);
        filter.setPassThrough(true);
        filter.setValue('10;33');

        // Act
        const result = filter.getFilterHeaderText();

        // Assert
        expect(result).toBe('Character level: 10 - 33');
    });

    test('getFilterHeaderText_givenDeathType_namesTheDeathType', () => {
        // Arrange
        const filter = new SearchFilterRadioDeathType(null, null, () => undefined, {passThrough: true});
        filter.setValue(COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH);

        // Act
        const result = filter.getFilterHeaderText();

        // Assert
        expect(result).toBe('Death type: Player death');
    });
});
