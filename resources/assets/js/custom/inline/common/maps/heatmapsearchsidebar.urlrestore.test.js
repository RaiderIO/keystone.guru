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
    'SearchFilterMythicLevel',
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
    `;
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
