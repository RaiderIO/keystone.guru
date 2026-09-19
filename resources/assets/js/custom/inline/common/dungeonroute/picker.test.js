// ---------------------------------------------------------------------------
// `picker.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals, so `InlineCode` must be on `globalThis` before
// the class body is evaluated (same pattern as orderedselect.test.js).
//
// The drawer is driven against a real jQuery over jsdom markup mirroring
// common/dungeonroute/picker.blade.php; `$.ajax` is replaced per test and
// `bootstrap.Offcanvas` is stubbed, since jsdom has neither a server nor Bootstrap.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonDungeonroutePicker} = require('./picker');

const OPTIONS = {
    drawerSelector:             '#picker',
    openButtonSelector:         '.open_picker',
    titleSearchSelector:        '#picker_title_search',
    dungeonSelectSelector:      '#picker_dungeon',
    affixSelectSelector:        '#picker_affixes',
    attributesSelectSelector:   '#picker_attributes',
    requirementsSelectSelector: '#picker_requirements',
    tagsSelectSelector:         '#picker_tags',
    listSelector:               '#picker_list',
    rowTemplateSelector:        '#picker_row_template',
    loadingSelector:            '#picker_loading',
    emptySelector:              '#picker_empty',
    errorSelector:              '#picker_error',
    previousSelector:           '#picker_previous',
    nextSelector:               '#picker_next',
    rangeSelector:              '#picker_range',
    selectionSelector:          '#picker_selection',
    fullSelector:               '#picker_full',
    addButtonSelector:          '#picker_add',
    statusSelector:             '#picker_status',
    listUrl:                    '/ajax/routes',
    pageSize:                   2,
    sourceParameters:           {mine: 1},
    lockedParameters:           {game_version_id: 1, season_id: 14, dungeon_ids: [3, 4]},
    existingPublicKeys:         ['existing'],
    max:                        3,
    addUrl:                     '/target/add',
    addFieldName:               'dungeon_routes',
    fallbackImageBaseUrl:       'https://assets/images',
    rangeText:                  ':from-:to of :total',
    keyLevelText:               '+:level',
    keyRangeText:               '+:min - +:max',
    enemyForcesText:            ':count/:required',
    selectedNoneText:           'None selected',
    selectedOneText:            '1 selected',
    selectedManyText:           ':count selected',
    fullText:                   'Limit is :max',
    addNoneText:                'Add routes',
    addOneText:                 'Add 1 route',
    addManyText:                'Add :count routes',
    addFailedText:              'Adding failed',
};

/**
 * @param {string} publicKey
 * @param {Object} overrides
 * @returns {Object}
 */
function route(publicKey, overrides = {}) {
    return Object.assign({
        public_key:                    publicKey,
        title:                         `Route ${publicKey}`,
        published:                     'world',
        level_min:                     2,
        level_max:                     10,
        teeming:                       0,
        enemy_forces:                  310,
        enemy_forces_required:         300,
        enemy_forces_required_teeming: 350,
        has_thumbnail:                 false,
        thumbnails:                    [],
        dungeon:                       {name: 'dungeons.ara_kara', key: 'arakara', expansion: {shortname: 'tww'}},
    }, overrides);
}

describe('CommonDungeonroutePicker', () => {
    let previousJquery;
    let previousBootstrap;
    let ajaxCalls;
    let offcanvas;
    let picker;

    beforeEach(() => {
        previousJquery    = globalThis.$;
        previousBootstrap = globalThis.bootstrap;
        globalThis.$      = jQuery;

        ajaxCalls = [];
        jQuery.ajax = vi.fn((settings) => {
            ajaxCalls.push(settings);

            return {abort: vi.fn()};
        });

        offcanvas = {show: vi.fn(), hide: vi.fn()};
        globalThis.bootstrap = {Offcanvas: {getOrCreateInstance: vi.fn(() => offcanvas)}};

        document.body.innerHTML = `
            <button class="open_picker">Open</button>
            <div id="picker">
                <input id="picker_title_search" value="">
                <select id="picker_dungeon"><option value="-1" selected>All</option><option value="3">Ara-Kara</option></select>
                <select id="picker_affixes" multiple><option value="7">Fortified</option></select>
                <select id="picker_attributes" multiple><option value="-1" selected>None</option></select>
                <select id="picker_requirements" multiple><option value="favorite">Favorite</option></select>
                <select id="picker_tags" multiple><option value="mine">mine</option></select>
                <p id="picker_loading" hidden></p>
                <p id="picker_empty" hidden></p>
                <p id="picker_error" hidden></p>
                <div aria-busy="false"><ul id="picker_list"></ul></div>
                <button id="picker_previous"></button>
                <span id="picker_range"></span>
                <button id="picker_next"></button>
                <span id="picker_selection"></span>
                <span id="picker_full" hidden></span>
                <button id="picker_add"></button>
                <div id="picker_status"></div>
                <template id="picker_row_template">
                    <li class="route_picker_row">
                        <label>
                            <input type="checkbox" class="route_picker_checkbox">
                            <img class="route_picker_thumbnail" src="" alt="">
                            <span class="route_picker_title"></span>
                            <span class="route_picker_dungeon"></span>
                            <span class="route_picker_key_range"></span>
                            <span class="route_picker_enemy_forces"></span>
                            <span class="route_picker_unpublished" hidden></span>
                            <span class="route_picker_already_in" hidden></span>
                        </label>
                    </li>
                </template>
            </div>`;

        picker = new CommonDungeonroutePicker('picker', 'common/dungeonroute/picker', Object.assign({}, OPTIONS));
        picker.activate();
    });

    afterEach(() => {
        globalThis.$         = previousJquery;
        globalThis.bootstrap = previousBootstrap;
        document.body.innerHTML = '';
    });

    /**
     * Answers the most recent list request.
     * @param {Object[]} rows
     * @param {Number} total
     */
    function respondWithRoutes(rows, total = rows.length) {
        const request = ajaxCalls.filter((call) => call.type === 'GET').pop();
        request.success({draw: request.data.draw, recordsFiltered: total, data: rows});
        request.complete();
    }

    /**
     * @param {string} publicKey
     * @returns {HTMLElement}
     */
    function rowOf(publicKey) {
        return document.querySelector(`#picker_list [data-public-key="${publicKey}"]`);
    }

    /**
     * @param {string} publicKey
     */
    function tick(publicKey) {
        rowOf(publicKey).querySelector('.route_picker_checkbox').click();
    }

    it('open_givenTheFirstShow_loadsTheFirstPageWithTheSourceAndLockedParameters', () => {
        // Arrange
        document.querySelector('#picker_title_search').value = '  Tyrannical  ';

        // Act
        document.querySelector('.open_picker').click();
        jQuery('#picker').trigger('show.bs.offcanvas');

        // Assert
        expect(offcanvas.show).toHaveBeenCalledTimes(1);
        expect(ajaxCalls).toHaveLength(1);
        const data = ajaxCalls[0].data;
        expect(ajaxCalls[0].url).toBe('/ajax/routes');
        expect(data.start).toBe(0);
        expect(data.length).toBe(2);
        expect(data.mine).toBe(1);
        expect(data.game_version_id).toBe(1);
        expect(data.season_id).toBe(14);
        expect(data.dungeon_ids).toEqual([3, 4]);
        expect(data.columns[0]).toMatchObject({name: 'title', search: {value: 'Tyrannical'}});
        expect(data.columns[1]).toMatchObject({name: 'dungeon_id', search: {value: '-1'}});
        expect(data.columns[3]).toMatchObject({name: 'routeattributes.name', search: {value: ['-1']}});
        expect(document.querySelector('#picker_loading').hidden).toBe(false);
    });

    it('open_givenADungeonAfterTheFirstLoad_putsTheDungeonFilterOnItAndLoadsAgain', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a')]);

        // Act
        picker.open({dungeonId: 3});

        // Assert
        expect(document.querySelector('#picker_dungeon').value).toBe('3');
        expect(ajaxCalls).toHaveLength(2);
        expect(ajaxCalls[1].data.columns[1].search.value).toBe('3');
        expect(offcanvas.show).toHaveBeenCalledTimes(1);
    });

    it('open_givenTheDungeonTheFilterIsAlreadyOn_doesNotLoadAgain', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a')]);

        // Act
        picker.open({dungeonId: -1});

        // Assert
        expect(ajaxCalls).toHaveLength(1);
    });

    it('load_givenRoutes_rendersTheRowDetailsAndMarksExistingAndUnpublishedRoutes', () => {
        // Arrange
        picker.reload();

        // Act
        respondWithRoutes([
            route('existing'),
            route('fresh', {published: 'unpublished', level_max: 2, has_thumbnail: true, thumbnails: [{url: 'https://thumb/1.jpg'}]}),
        ], 5);

        // Assert
        const existing = rowOf('existing');
        expect(existing.querySelector('.route_picker_checkbox').checked).toBe(true);
        expect(existing.querySelector('.route_picker_checkbox').disabled).toBe(true);
        expect(existing.querySelector('.route_picker_already_in').hidden).toBe(false);
        expect(existing.querySelector('.route_picker_key_range').textContent).toBe('+2 - +10');
        expect(existing.querySelector('.route_picker_enemy_forces').textContent).toBe('310/300');
        expect(existing.querySelector('.route_picker_thumbnail').getAttribute('src')).toBe('https://assets/images/dungeons/tww/arakara_3-2.jpg');

        const fresh = rowOf('fresh');
        expect(fresh.querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(fresh.querySelector('.route_picker_unpublished').hidden).toBe(false);
        expect(fresh.querySelector('.route_picker_key_range').textContent).toBe('+2');
        expect(fresh.querySelector('.route_picker_thumbnail').getAttribute('src')).toBe('https://thumb/1.jpg');

        expect(document.querySelector('#picker_range').textContent).toBe('1-2 of 5');
        expect(document.querySelector('#picker_previous').disabled).toBe(true);
        expect(document.querySelector('#picker_next').disabled).toBe(false);
    });

    it('load_givenNoRoutes_showsTheEmptyState', () => {
        // Arrange
        picker.reload();

        // Act
        respondWithRoutes([]);

        // Assert
        expect(document.querySelector('#picker_empty').hidden).toBe(false);
        expect(document.querySelector('#picker_next').disabled).toBe(true);
    });

    it('load_givenAnOlderResponseArrivesLast_keepsTheNewestRows', () => {
        // Arrange
        picker.reload();
        const olderRequest = ajaxCalls[0];
        picker.reload();
        respondWithRoutes([route('newest')]);

        // Act
        olderRequest.success({draw: olderRequest.data.draw, recordsFiltered: 1, data: [route('stale')]});

        // Assert
        expect(rowOf('newest')).not.toBeNull();
        expect(rowOf('stale')).toBeNull();
    });

    it('goToPage_givenNext_requestsTheNextPage', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a'), route('b')], 5);

        // Act
        document.querySelector('#picker_next').click();

        // Assert
        expect(ajaxCalls[1].data.start).toBe(2);
    });

    it('onCheckboxChanged_givenTicksUpToMax_disablesTheRemainingRowsWithTheReason', () => {
        // Arrange - max 3 with one route already in, so two more fit
        picker.reload();
        respondWithRoutes([route('a'), route('b'), route('c')]);

        // Act
        tick('a');
        tick('b');

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a', 'b']);
        expect(rowOf('c').querySelector('.route_picker_checkbox').disabled).toBe(true);
        expect(rowOf('a').querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(document.querySelector('#picker_full').hidden).toBe(false);
        expect(document.querySelector('#picker_full').textContent).toBe('Limit is 3');
        expect(document.querySelector('#picker_add').textContent).toBe('Add 2 routes');
        expect(document.querySelector('#picker_selection').textContent).toBe('2 selected');
    });

    it('onCheckboxChanged_givenNoMax_neverDisablesARow', () => {
        // Arrange
        picker.options.max = null;
        picker.reload();
        respondWithRoutes([route('a'), route('b'), route('c')]);

        // Act
        tick('a');
        tick('b');

        // Assert
        expect(rowOf('c').querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(document.querySelector('#picker_full').hidden).toBe(true);
    });

    it('onCheckboxChanged_givenATickIsUndone_removesItFromTheSelection', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a')]);
        tick('a');

        // Act
        tick('a');

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual([]);
        expect(document.querySelector('#picker_add').disabled).toBe(true);
        expect(document.querySelector('#picker_add').textContent).toBe('Add routes');
    });

    it('load_givenAnotherPage_keepsTicksFromTheFirstPage', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a'), route('b')], 4);
        tick('a');
        document.querySelector('#picker_next').click();
        respondWithRoutes([route('c'), route('d')], 4);

        // Act
        document.querySelector('#picker_previous').click();
        respondWithRoutes([route('a'), route('b')], 4);

        // Assert
        expect(rowOf('a').querySelector('.route_picker_checkbox').checked).toBe(true);
        expect(picker.getSelectedPublicKeys()).toEqual(['a']);
    });

    it('add_givenTickedRoutes_postsThemAndReportsTheResultToTheHost', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);
        tick('b');
        tick('a');
        const callback = vi.fn();
        const eventHandler = vi.fn();
        picker.onAdded(callback);
        jQuery('#picker').on('routepicker:added', eventHandler);

        // Act
        document.querySelector('#picker_add').click();
        const post = ajaxCalls.find((call) => call.type === 'POST');
        post.success({added: 2});
        post.complete();

        // Assert
        expect(post.url).toBe('/target/add');
        expect(post.data).toEqual({dungeon_routes: ['b', 'a']});
        expect(callback).toHaveBeenCalledWith({publicKeys: ['b', 'a'], response: {added: 2}});
        expect(eventHandler.mock.calls[0][1]).toEqual({publicKeys: ['b', 'a'], response: {added: 2}});
        expect(offcanvas.hide).toHaveBeenCalledTimes(1);
        expect(picker.getSelectedPublicKeys()).toEqual([]);
        expect(rowOf('a').querySelector('.route_picker_already_in').hidden).toBe(false);
    });

    it('add_givenTheEndpointFails_keepsTheSelectionAndSaysSo', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a')]);
        tick('a');

        // Act
        document.querySelector('#picker_add').click();
        const post = ajaxCalls.find((call) => call.type === 'POST');
        post.error({status: 422}, 'error');
        post.complete();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a']);
        expect(document.querySelector('#picker_status').textContent).toBe('Adding failed');
        expect(document.querySelector('#picker_add').disabled).toBe(false);
        expect(offcanvas.hide).not.toHaveBeenCalled();
    });

    it('setExistingPublicKeys_givenAnUndoneAdd_makesTheRouteTickableAgain', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('existing')]);

        // Act
        picker.setExistingPublicKeys([]);

        // Assert
        const checkbox = rowOf('existing').querySelector('.route_picker_checkbox');
        expect(checkbox.checked).toBe(false);
        expect(checkbox.disabled).toBe(false);
        expect(rowOf('existing').querySelector('.route_picker_already_in').hidden).toBe(true);
    });
});
