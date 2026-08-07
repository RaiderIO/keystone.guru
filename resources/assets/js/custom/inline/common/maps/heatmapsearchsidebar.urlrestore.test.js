// ---------------------------------------------------------------------------
// Regression suite for #3744: a heatmap link only survives a reload if restoring the filters from the URL
// also applies the event type's side effects. Restoring a radio uses .prop('checked', true), which does not
// fire a change event, so before the fix the dependent filters stayed enabled (leaking dataType=player_position
// into the URL) and their containers stayed hidden (so the restored spell selection was nowhere on screen).
//
// The sidebar is driven for real - real jQuery and the real SearchInlineBase/SearchParams/SearchFilter*
// classes - mirroring the pattern in custom/inline/common/forms/createroute.serialization.test.js. The
// filters that aren't involved in the event type's side effects are stubbed; they'd otherwise only add DOM
// to maintain. The contract asserted is the user-visible one: the set of params written back to the URL
// equals the set that was loaded.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {
    COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
    COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH,
    COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL,
    COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION,
    COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION,
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

// Filter class hierarchy - each file expects its parent as a bare global, so require them parent first.
const {SearchFilter} = require('../../common/search/filters/filter');
globalThis.SearchFilter = SearchFilter;
const {SearchFilterInput} = require('../../common/search/filters/filterinput');
globalThis.SearchFilterInput = SearchFilterInput;
const {SearchFilterRadio} = require('../../common/search/filters/filterradio');
globalThis.SearchFilterRadio = SearchFilterRadio;
const {SearchFilterSelect} = require('../../common/search/filters/filterselect');
globalThis.SearchFilterSelect = SearchFilterSelect;
globalThis.SearchFilterRadioEventType = require('../../common/search/filters/filterradioeventtype').SearchFilterRadioEventType;
globalThis.SearchFilterRadioDataType = require('../../common/search/filters/filterradiodatatype').SearchFilterRadioDataType;
globalThis.SearchFilterRadioRegion = require('../../common/search/filters/filterradioregion').SearchFilterRadioRegion;
globalThis.SearchFilterPlayerSpells = require('../../common/search/filters/filterselectplayerspells').SearchFilterPlayerSpells;
globalThis.SearchFilterPassThrough = require('../../common/search/filters/filterpassthrough').SearchFilterPassThrough;
const {SearchFilterKeyLevel} = require('../../common/search/filters/filterinputkeylevel');
globalThis.SearchFilterKeyLevel = SearchFilterKeyLevel;
// The real key level filter is kept (rather than stubbed like its siblings) because it is the one filter here that
// claims its URL params through getParamsOverride() - which is where #3837's minMythicLevel truncation lived.
globalThis.SearchFilterMythicLevel = require('../../common/search/filters/filterinputmythiclevel').SearchFilterMythicLevel;

globalThis.SearchParams = require('../../common/search/searchparams').SearchParams;
const {SearchInlineBase} = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;

// Filters that have no part in the event type's side effects: an always-empty value keeps them out of the URL.
class StubFilter extends SearchFilter {
    getValue() {
        return '';
    }
}

for (const name of [
    'SearchFilterItemLevel',
    'SearchFilterPlayerDeaths',
    'SearchFilterAffixes',
    'SearchFilterWeeklyAffixGroups',
    'SearchFilterClasses',
    'SearchFilterSpecializations',
    'SearchFilterClassesPlayerDeaths',
    'SearchFilterSpecializationsPlayerDeaths',
    'SearchFilterDuration',
]) {
    globalThis[name] = StubFilter;
}

for (const name of [
    'KeyLevelHandler',
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

globalThis.getState = () => ({
    userHasRole: () => false,
    getDungeonMap: () => ({
        pluginHeat: {
            toggle: () => undefined,
            setRawLatLngsPerFloor: () => undefined,
        },
    }),
    getMapContext: () => ({getDungeon: () => ({id: DUNGEON_ID})}),
});

const {CommonMapsHeatmapsearchsidebar} = require('./heatmapsearchsidebar');

const DUNGEON_ID = 42;
const BLOODLUST_SPELL_ID = 2825;
const HEROISM_SPELL_ID = 32182;
const TIME_WARP_SPELL_ID = 80353;
const SPELL_IDS = [BLOODLUST_SPELL_ID, HEROISM_SPELL_ID, TIME_WARP_SPELL_ID];
// The season's own key level bounds - #3837 is about an explicit ?minMythicLevel that happens to equal this minimum.
const KEY_LEVEL_MIN = 2;
const KEY_LEVEL_MAX = 99;

const OPTIONS = {
    defaultState: 0,
    enabledStateCookie: 'heatmap_search_enabled',
    enabledStateSelector: '#heatmap_search_toggle',
    filterEventTypeContainerSelector: '#filter_event_type_container',
    filterEventTypeSelector: 'input[name="event_type"]',
    filterDataTypeContainerSelector: '#filter_data_type_container',
    filterDataTypeSelector: 'input[name="data_type"]',
    filterPlayerSpellsContainerSelector: '#filter_player_spells_container',
    filterPlayerSpellsSelector: '#filter_player_spells',
    filterRegionContainerSelector: '#filter_region_container',
    filterRegionSelector: 'input[name="region"]',
    filterClassesPlayerDeathsContainerSelector: '#filter_classes_player_deaths_container',
    filterClassesPlayerDeathsSelector: '#filter_classes_player_deaths',
    filterSpecializationsPlayerDeathsContainerSelector: '#filter_specializations_player_deaths_container',
    filterSpecializationsPlayerDeathsSelector: '#filter_specializations_player_deaths',
    filterKeyLevelSelector: '#filter_key_level',
    keyLevelMin: KEY_LEVEL_MIN,
    keyLevelMax: KEY_LEVEL_MAX,
};

/**
 * Builds the subset of common/maps/controls/heatmapsearch.blade.php the event type's side effects touch.
 * The data type and player spells containers are rendered hidden, exactly like the blade does through
 * common/forms/labelinput.blade.php's `hidden` flag.
 */
function buildSidebarDom() {
    document.body.innerHTML = `
        <div id="map"></div>
        <input type="checkbox" id="heatmap_search_toggle" checked>

        <div id="filter_event_type_container">
            <input type="radio" name="event_type" class="btn-check ${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}" value="${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}" checked>
            <input type="radio" name="event_type" class="btn-check ${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}" value="${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}">
            <input type="radio" name="event_type" class="btn-check ${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}" value="${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}">
        </div>

        <div id="filter_data_type_container" style="display: none;">
            <input type="radio" name="data_type" class="btn-check ${COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION}" value="${COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION}" checked>
            <input type="radio" name="data_type" class="btn-check ${COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION}" value="${COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION}">
        </div>

        <div id="filter_player_spells_container" style="display: none;">
            <select id="filter_player_spells" class="form-control selectpicker" multiple>
                ${SPELL_IDS.map((spellId) => `<option value="${spellId}">Spell ${spellId}</option>`).join('')}
                <option value="264667">Spell 264667</option>
            </select>
        </div>

        <div id="filter_classes_player_deaths_container" style="display: none;"></div>
        <div id="filter_specializations_player_deaths_container" style="display: none;"></div>

        <div id="filter_region_container">
            <input type="radio" name="region" class="btn-check world" value="world" checked>
            <input type="radio" name="region" class="btn-check us" value="us">
        </div>

        <input type="text" id="filter_key_level" value="${KEY_LEVEL_MIN};${KEY_LEVEL_MAX}">
    `;

    // Stands in for the ion-range-slider instance KeyLevelHandler attaches: the filter reads the input's value and
    // writes through the slider's update() - both of which have to keep the `min;max` string in sync.
    const $keyLevel = $('#filter_key_level');
    $keyLevel.data('rangeSlider', {
        update: ({from, to}) => $keyLevel.val(`${from};${to}`),
    });
}

/**
 * @param {String} queryString
 * @param {Object} optionOverrides
 * @returns {CommonMapsHeatmapsearchsidebar}
 */
function activateSidebar(queryString, optionOverrides = {}) {
    buildSidebarDom();
    // jsdom doesn't allow assigning document.location.search - see custom/util.test.js for the same approach.
    window.history.replaceState({}, '', `/heatmap/retail/algethar-academy/1${queryString}`);

    const sidebar = new CommonMapsHeatmapsearchsidebar(
        'heatmapsearchsidebar',
        'common/maps/heatmapsearchsidebar',
        $.extend({}, OPTIONS, optionOverrides)
    );
    sidebar.activate();

    return sidebar;
}

/**
 * The params the sidebar wrote back into the address bar, as a plain object.
 *
 * Comparing param *sets* rather than the raw string is deliberate: _updateUrl() rebuilds the query string in
 * filter declaration order and re-encodes every value, so a semantically identical URL is routinely a
 * different string.
 *
 * @returns {Object<string, string>}
 */
function writtenParams() {
    return Object.fromEntries(new URLSearchParams(window.location.search));
}

/**
 * @returns {boolean}
 */
function isVisible(selector) {
    return document.querySelector(selector).style.display !== 'none';
}

describe('CommonMapsHeatmapsearchsidebar - restoring filters from the URL', () => {
    beforeEach(() => {
        searchSpy.mockClear();
    });

    test('activate_givenPlayerSpellEventTypeInUrl_showsSpellFilterAndHidesDataTypeFilter', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}&includePlayerSpellIds=${SPELL_IDS.join(',')}&region=world`);

        // Assert
        expect(isVisible('#filter_player_spells_container')).toBe(true);
        expect(isVisible('#filter_data_type_container')).toBe(false);
        expect(isVisible('#filter_classes_player_deaths_container')).toBe(false);
    });

    test('activate_givenPlayerSpellEventTypeInUrl_omitsDataTypeFromWrittenUrl', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}&includePlayerSpellIds=${SPELL_IDS.join(',')}&region=world`);

        // Assert
        expect(writtenParams()).toEqual({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL,
            includePlayerSpellIds: SPELL_IDS.join(','),
            region: 'world',
        });
    });

    test('activate_givenPlayerDeathEventTypeInUrl_showsPlayerDeathFiltersAndOmitsDataType', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}&region=world`);

        // Assert
        expect(isVisible('#filter_classes_player_deaths_container')).toBe(true);
        expect(isVisible('#filter_specializations_player_deaths_container')).toBe(true);
        expect(isVisible('#filter_player_spells_container')).toBe(false);
        expect(writtenParams()).not.toHaveProperty('dataType');
    });

    test('activate_givenNpcDeathEventTypeInUrl_keepsDataTypeInWrittenUrl', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}&dataType=${COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION}&region=world`);

        // Assert - the data type only applies to npc deaths, so there it must survive the restore
        expect(isVisible('#filter_data_type_container')).toBe(true);
        expect(writtenParams()).toEqual({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
            dataType: COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION,
            region: 'world',
        });
    });

    test('activate_givenNoQueryParams_writesTheDefaultNpcDeathFilters', () => {
        // Arrange & Act
        activateSidebar('');

        // Assert - a first visit is an npc death heatmap, so the data type does belong in the URL
        expect(writtenParams()).toEqual({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
            dataType: COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION,
            region: 'world',
        });
    });

    test('activate_givenNoQueryParams_showsTheDataTypeFilterItWritesToTheUrl', () => {
        // Arrange & Act
        activateSidebar('');

        // Assert - the data type applies to npc deaths, so on a first visit its control must be reachable
        expect(isVisible('#filter_data_type_container')).toBe(true);
        expect(isVisible('#filter_player_spells_container')).toBe(false);
    });

    test('activate_givenPassThroughEverything_leavesTheFiltersItWasGivenAlone', () => {
        // Arrange & Act - a sidebar-less embed is fully driven by whoever embedded us
        activateSidebar(
            `?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}&dataType=${COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION}&region=world`,
            {passThroughEverything: true}
        );

        // Assert
        expect(searchSpy.mock.calls[0][0].params.dataType).toBe(COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION);
    });

    test('searchWithFilters_givenPlayerSpellEventType_appliesTheEventTypeSideEffects', () => {
        // Arrange - an embed with a visible sidebar posts its filters in through postMessage
        const sidebar = activateSidebar('');
        searchSpy.mockClear();

        // Act
        sidebar.searchWithFilters({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL,
            includePlayerSpellIds: SPELL_IDS.join(','),
        });

        // Assert
        expect(isVisible('#filter_player_spells_container')).toBe(true);
        expect(isVisible('#filter_data_type_container')).toBe(false);
        expect(searchSpy.mock.calls[0][0].params).not.toHaveProperty('dataType');
        // The disabled -> enabled direction: the spells only reach the request once the event type enables them
        expect(searchSpy.mock.calls[0][0].params.includePlayerSpellIds)
            .toEqual(SPELL_IDS.map((spellId) => `${spellId}`));
    });

    test('activate_givenAFilteredUrl_writesBackTheSameParams', () => {
        // Arrange
        const queryString = `?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}&includePlayerSpellIds=${SPELL_IDS.join(',')}&region=world`;

        // Act
        activateSidebar(queryString);

        // Assert - pressing F5 on a shared link must not change what is being filtered on
        expect(writtenParams()).toEqual(Object.fromEntries(new URLSearchParams(queryString)));
    });

    test('activate_givenAFilteredUrl_searchesWithTheRestoredFilters', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}&includePlayerSpellIds=${SPELL_IDS.join(',')}&region=world`);

        // Assert - the filters aren't just cosmetic, they have to reach the backend too
        expect(searchSpy).toHaveBeenCalledTimes(1);
        expect(searchSpy.mock.calls[0][0].params).toEqual({
            dungeonId: DUNGEON_ID,
            type: COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL,
            includePlayerSpellIds: SPELL_IDS.map((spellId) => `${spellId}`),
            region: 'world',
        });
    });

    test('changeEventType_givenARestoredUrl_togglesTheDependentFiltersAsBefore', () => {
        // Arrange
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL}&includePlayerSpellIds=${SPELL_IDS.join(',')}&region=world`);

        // Act - click the 'NPC deaths' tab
        $(`input[name="event_type"].${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}`).prop('checked', true).trigger('change');

        // Assert
        expect(isVisible('#filter_data_type_container')).toBe(true);
        expect(isVisible('#filter_player_spells_container')).toBe(false);
        expect(writtenParams()).toEqual({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
            dataType: COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION,
            region: 'world',
        });
    });
});

// ---------------------------------------------------------------------------
// Regression suite for #3837: an embed URL carries display-only options next to the search filters
// (defaultZoom, showHeader, mapFacadeStyle, ...). The sidebar's initial search used to rebuild the query string
// from its filters alone, so those options - and any filter value that happened to equal its own default -
// were silently dropped from the address bar, and an F5 came back with a differently rendered page.
// ---------------------------------------------------------------------------
describe('CommonMapsHeatmapsearchsidebar - preserving embed params in the URL', () => {
    const EMBED_PARAMS = {
        defaultZoom: '1.4',
        showHeader: '0',
        showDataSourceSnackbar: '0',
        mapFacadeStyle: 'facade',
    };
    const EMBED_QUERY_STRING = Object.entries(EMBED_PARAMS).map(([key, value]) => `${key}=${value}`).join('&');

    beforeEach(() => {
        searchSpy.mockClear();
    });

    test('activate_givenDisplayOnlyEmbedParams_keepsThemInTheUrl', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}&dataType=${COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION}&region=world&${EMBED_QUERY_STRING}`);

        // Assert - none of these belong to a filter, so the sidebar must leave them exactly as it found them
        expect(writtenParams()).toEqual({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH,
            dataType: COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION,
            region: 'world',
            ...EMBED_PARAMS,
        });
    });

    test('activate_givenDisplayOnlyEmbedParams_doesNotSendThemToTheBackend', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}&region=world&${EMBED_QUERY_STRING}`);

        // Assert - they're display options for this page, the heatmap endpoint has no use for them
        for (const key of Object.keys(EMBED_PARAMS)) {
            expect(searchSpy.mock.calls[0][0].params).not.toHaveProperty(key);
        }
    });

    test('changeEventType_givenDisplayOnlyEmbedParams_keepsThemInTheUrl', () => {
        // Arrange
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH}&region=world&${EMBED_QUERY_STRING}`);

        // Act - a search after the initial restore rewrites the URL again, and must not drop them either
        $(`input[name="event_type"].${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}`).prop('checked', true).trigger('change');

        // Assert
        expect(writtenParams()).toMatchObject(EMBED_PARAMS);
    });

    test('activate_givenADisabledFiltersParamInTheUrl_stillDropsIt', () => {
        // Arrange & Act - the data type filter only applies to npc deaths
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}&dataType=${COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION}&region=world&${EMBED_QUERY_STRING}`);

        // Assert - preserving unknown params must not turn into preserving params the filters do own
        expect(writtenParams()).not.toHaveProperty('dataType');
        expect(writtenParams()).toMatchObject(EMBED_PARAMS);
    });

    test('activate_givenAMinMythicLevelEqualToTheSeasonDefault_keepsItInTheUrl', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}&region=world&minMythicLevel=${KEY_LEVEL_MIN}&maxMythicLevel=${KEY_LEVEL_MAX}`);

        // Assert - an explicitly requested value must round trip even when it matches the filter's own default
        expect(writtenParams()).toEqual({
            type: COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH,
            region: 'world',
            minMythicLevel: `${KEY_LEVEL_MIN}`,
            maxMythicLevel: `${KEY_LEVEL_MAX}`,
        });
    });

    test('activate_givenANonDefaultMinMythicLevel_keepsItInTheUrl', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}&region=world&minMythicLevel=10`);

        // Assert
        expect(writtenParams().minMythicLevel).toBe('10');
    });

    test('activate_givenNoMythicLevelParams_leavesThemOutOfTheUrl', () => {
        // Arrange & Act
        activateSidebar(`?type=${COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH}&region=world`);

        // Assert - untouched filters stay implicit, so a plain link doesn't grow every default under the sun
        expect(writtenParams()).not.toHaveProperty('minMythicLevel');
        expect(writtenParams()).not.toHaveProperty('maxMythicLevel');
    });
});
