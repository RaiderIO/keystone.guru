// ---------------------------------------------------------------------------
// `picker.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals, so `InlineCode` must be on `globalThis` before
// the class body is evaluated (same pattern as orderedselect.test.js).
//
// The drawer is driven against a real jQuery over jsdom markup mirroring
// common/dungeonroute/picker.blade.php; `$.ajax` is replaced per test and
// `bootstrap.Offcanvas` is stubbed, since jsdom has neither a server nor Bootstrap.
// ---------------------------------------------------------------------------

const fs         = require('node:fs');
const path       = require('node:path');
const jQuery     = require('jquery');
const Handlebars = require('handlebars');
const Lang       = require('lang.js');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {SearchInlineBase}    = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;
globalThis.SearchParams     = require('../search/searchparams').SearchParams;
globalThis.SearchHandler    = require('../search/searchhandler').SearchHandler;
globalThis.SearchHandlerDungeonRoutePicker = require('../search/searchhandlerdungeonroutepicker').SearchHandlerDungeonRoutePicker;
globalThis.SearchFilter            = require('../search/filters/filter').SearchFilter;
globalThis.SearchFilterInput       = require('../search/filters/filterinput').SearchFilterInput;
globalThis.SearchFilterInputChange = require('../search/filters/filterinputchange').SearchFilterInputChange;
globalThis.SearchFilterInputText   = require('../search/filters/filterinputtext').SearchFilterInputText;
globalThis.SearchFilterTitle       = require('../search/filters/filterinputtexttitle').SearchFilterTitle;
globalThis.DrawerDialog            = require('../drawer/drawerdialog').DrawerDialog;
globalThis.PickerDungeonRoute      = require('./pickerdungeonroute').PickerDungeonRoute;

const {CommonDungeonroutePicker} = require('./picker');

const MESSAGES = {
    'en.js':       {
        dungeonroute_picker_range:             ':from-:to of :total',
        dungeonroute_picker_already_in_label:  'Already added',
        dungeonroute_picker_unpublished_label: 'Unpublished',
        dungeonroute_picker_key_level:         '+:level',
        dungeonroute_picker_key_range:         '+:min - +:max',
        dungeonroute_picker_enemy_forces:      ':count/:required',
        dungeonroute_picker_pulls_one:         '1 pull',
        dungeonroute_picker_pulls_many:        ':count pulls',
        dungeonroute_picker_views:             ':count views',
        dungeonroute_picker_votes:             ':count votes',
        dungeonroute_picker_selected_none:     'None selected',
        dungeonroute_picker_selected_one:      '1 selected',
        dungeonroute_picker_selected_many:     ':count selected',
        dungeonroute_picker_full:              'Limit is :max',
        dungeonroute_picker_dungeon_full:      'Dungeon limit is :max',
        dungeonroute_picker_add_none:          'Add routes',
        dungeonroute_picker_add_one:           'Add 1 route',
        dungeonroute_picker_add_many:          'Add :count routes',
        dungeonroute_picker_add_failed:        'Adding failed',
        dungeonroute_picker_delete_none:          'Delete routes',
        dungeonroute_picker_delete_one:           'Delete 1 route',
        dungeonroute_picker_delete_many:          'Delete :count routes',
        dungeonroute_picker_delete_confirm_one:   'Delete this route permanently?',
        dungeonroute_picker_delete_confirm_many:  'Delete these :count routes permanently?',
        dungeonroute_picker_delete_failed:        'Deleting failed',
        dungeonroute_picker_delete_full:          'Delete at most :max',
    },
    'en.dungeons': {ara_kara: 'Ara-Kara'},
};

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
    loadingSelector:            '#picker_loading',
    emptySelector:              '#picker_empty',
    errorSelector:              '#picker_error',
    previousSelector:           '#picker_previous',
    nextSelector:               '#picker_next',
    rangeSelector:              '#picker_range',
    selectionSelector:          '#picker_selection',
    fullSelector:               '#picker_full',
    confirmButtonSelector:      '#picker_confirm',
    statusSelector:             '#picker_status',
    listUrl:                    '/ajax/routes',
    pageSize:                   2,
    sourceParameters:           {mine: 1},
    lockedParameters:           {game_version_id: 1, season_id: 14, dungeon_ids: [3, 4]},
    existingPublicKeys:         ['existing'],
    max:                        3,
    actionKeyPrefix:            'dungeonroute_picker_add',
    actionUrl:                  '/target/add',
    actionFieldName:            'dungeon_routes',
    actionMethod:               'POST',
    confirmsAction:             false,
    fallbackImageBaseUrl:       'https://assets/images',
    affixGroups:                {7: [{class: 'fortified', name: 'Fortified'}]},
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
        views:                         1500,
        rating:                        8,
        rating_count:                  4,
        enemy_forces:                  310,
        enemy_forces_required:         300,
        enemy_forces_required_teeming: 350,
        pull_forces:                   [{enemy_forces: 40, has_boss: false}, {enemy_forces: 0, has_boss: true}],
        has_thumbnail:                 false,
        thumbnails:                    [],
        dungeon:                       {id: 3, name: 'dungeons.ara_kara', key: 'arakara', expansion: {shortname: 'tww'}},
    }, overrides);
}

const MARKUP = `
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
                <div aria-busy="false">
                    <div class="route_picker_select_page" hidden><input id="picker_select_page" type="checkbox"></div>
                    <ul id="picker_list"></ul>
                </div>
                <button id="picker_previous"></button>
                <span id="picker_range"></span>
                <button id="picker_next"></button>
                <span id="picker_selection"></span>
                <span id="picker_full" hidden></span>
                <button id="picker_confirm"></button>
                <div id="picker_status"></div>
            </div>`;

describe('CommonDungeonroutePicker', () => {
    let previousJquery;
    let previousBootstrap;
    const templatesDir = path.join(__dirname, '../../../../handlebars');
    let ajaxCalls;
    let offcanvas;
    let picker;

    beforeEach(() => {
        previousJquery    = globalThis.$;
        previousBootstrap = globalThis.bootstrap;
        globalThis.$      = jQuery;

        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});
        globalThis.refreshSelectPickers          = vi.fn();
        globalThis.showConfirmYesCancel          = vi.fn();
        globalThis.getHandlebarsDefaultVariables = () => MESSAGES['en.js'];
        globalThis.Handlebars                    = Handlebars;
        Handlebars.templates = {};
        ['dungeonroute_picker_row', 'affixgroup_select_option_template'].forEach((name) => {
            Handlebars.templates[name] = Handlebars.compile(
                fs.readFileSync(path.join(templatesDir, `${name}.handlebars`), 'utf8'),
            );
        });

        ajaxCalls = [];
        jQuery.ajax = vi.fn((settings) => {
            ajaxCalls.push(settings);
            if (typeof settings.beforeSend === 'function') {
                settings.beforeSend();
            }

            settings.xhr = {abort: vi.fn()};

            return settings.xhr;
        });

        offcanvas = {show: vi.fn(), hide: vi.fn()};
        globalThis.bootstrap = {Offcanvas: {getOrCreateInstance: vi.fn(() => offcanvas)}};

        document.body.innerHTML = MARKUP;

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
        request.success({draw: request.data.draw, recordsFiltered: total, data: rows}, 'success', {status: 200});
        request.complete({}, 'success');
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
        expect(data.with_pull_forces).toBe(1);
        expect(document.querySelector('#picker_loading').hidden).toBe(false);
    });

    it('open_givenADungeonAfterTheFirstLoad_putsTheDungeonFilterOnItAndLoadsAgain', () => {
        // Arrange
        jQuery('#picker').trigger('show.bs.offcanvas');
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
        jQuery('#picker').trigger('show.bs.offcanvas');
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
            route('fresh', {published: 'unpublished', level_max: 2, enemy_forces: 290, has_thumbnail: true, thumbnails: [{url: 'https://thumb/1.jpg'}]}),
        ], 5);

        // Assert
        const existing = rowOf('existing');
        expect(existing.querySelector('.route_picker_checkbox').checked).toBe(true);
        expect(existing.querySelector('.route_picker_checkbox').disabled).toBe(true);
        expect(existing.querySelector('.route_picker_already_in').hidden).toBe(false);
        expect(existing.querySelector('.route_picker_key_range').textContent).toBe('+2 - +10');
        // Enough enemy forces, so the row says nothing about them - just like the route rows on the site
        expect(existing.querySelector('.route_picker_enemy_forces')).toBeNull();
        expect(existing.querySelector('.route_picker_views').textContent).toContain('1.5K');
        expect(existing.querySelector('.route_picker_views').getAttribute('title')).toBe('1500 views');
                expect(existing.querySelector('.route_picker_rating').getAttribute('title')).toBe('4 votes');
        expect(existing.querySelector('.route_picker_rating').querySelectorAll('.fas.fa-star')).toHaveLength(4);
        expect(existing.querySelector('.route_picker_thumbnail').style.backgroundImage)
            .toContain('https://assets/images/dungeons/tww/arakara_3-2.jpg');

        // One bar per pull that carries forces or a boss, the boss bar full height in the accent colour
        const graph = existing.querySelector('.route_picker_pull_graph');
        expect(graph.querySelectorAll('svg rect')).toHaveLength(2);
        expect(graph.querySelectorAll('svg rect')[1].getAttribute('height')).toBe('22');
        expect(graph.querySelector('[data-bs-toggle="tooltip"]').getAttribute('title')).toBe('2 pulls');

        const fresh = rowOf('fresh');
        expect(fresh.querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(fresh.querySelector('.route_picker_unpublished')).not.toBeNull();
        expect(existing.querySelector('.route_picker_unpublished')).toBeNull();
        expect(fresh.querySelector('.route_picker_key_range').textContent).toBe('+2');
                expect(fresh.querySelector('.route_picker_enemy_forces').textContent).toContain('290/300');
        expect(fresh.querySelector('.route_picker_thumbnail').style.backgroundImage).toContain('https://thumb/1.jpg');

        expect(document.querySelector('#picker_range').textContent).toBe('1-2 of 5');
        expect(document.querySelector('#picker_previous').disabled).toBe(true);
        expect(document.querySelector('#picker_next').disabled).toBe(false);
    });

    it('load_givenARouteWithoutPulls_leavesTheGraphSlotEmptyButInPlace', () => {
        // Arrange
        picker.reload();

        // Act - a pull granting no forces and holding no boss says nothing, so no bar is drawn for it
        respondWithRoutes([route('a', {pull_forces: [{enemy_forces: 0, has_boss: false}]})]);

        // Assert
        const graph = rowOf('a').querySelector('.route_picker_pull_graph');
        expect(graph).not.toBeNull();
        expect(graph.innerHTML.trim()).toBe('');
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

    it('reload_givenARequestStillInFlight_abortsItSoItCannotOverwriteTheNewestRows', () => {
        // Arrange
        picker.reload();
        const olderRequest = ajaxCalls[0];

        // Act
        picker.reload();

        // Assert
        expect(olderRequest.xhr.abort).toHaveBeenCalledTimes(1);
        expect(ajaxCalls).toHaveLength(2);
    });

    it('titleFilter_givenItLosesFocusUnchanged_keepsTheListedRowsAndTheirTicks', () => {
        // Arrange
        jQuery('#picker').trigger('show.bs.offcanvas');
        respondWithRoutes([route('a')]);
        tick('a');

        // Act
        jQuery('#picker_title_search').trigger('focusout');

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect(rowOf('a').querySelector('.route_picker_checkbox').checked).toBe(true);
    });

    it('titleFilter_givenItLosesFocusUnchangedOnALaterPage_staysOnThatPage', () => {
        // Arrange
        jQuery('#picker').trigger('show.bs.offcanvas');
        respondWithRoutes([route('a'), route('b')], 5);
        document.querySelector('#picker_next').click();
        respondWithRoutes([route('c')], 5);

        // Act
        jQuery('#picker_title_search').trigger('focusout');

        // Assert
        expect(ajaxCalls).toHaveLength(2);
        expect(rowOf('c')).not.toBeNull();
    });

    it('show_givenTheListFailedToLoad_triesAgain', () => {
        // Arrange
        jQuery('#picker').trigger('show.bs.offcanvas');
        ajaxCalls[0].error({}, 'error');

        // Act
        jQuery('#picker').trigger('show.bs.offcanvas');

        // Assert
        expect(ajaxCalls).toHaveLength(2);
        expect(document.querySelector('#picker_loading').hidden).toBe(false);
    });

    it('show_givenTheListLoaded_doesNotLoadAgain', () => {
        // Arrange
        jQuery('#picker').trigger('show.bs.offcanvas');
        respondWithRoutes([route('a')]);

        // Act
        jQuery('#picker').trigger('show.bs.offcanvas');

        // Assert
        expect(ajaxCalls).toHaveLength(1);
    });

    it('titleFilter_givenItLosesFocusAfterTheListFailedToLoad_triesAgain', () => {
        // Arrange
        jQuery('#picker').trigger('show.bs.offcanvas');
        ajaxCalls[0].error({}, 'error');

        // Act
        jQuery('#picker_title_search').trigger('focusout');

        // Assert
        expect(ajaxCalls).toHaveLength(2);
    });

    it('titleFilter_givenANewTitleAndEnter_listsTheFirstPageForIt', () => {
        // Arrange
        jQuery('#picker').trigger('show.bs.offcanvas');
        respondWithRoutes([route('a'), route('b')], 5);
        document.querySelector('#picker_next').click();
        respondWithRoutes([route('c')], 5);
        document.querySelector('#picker_title_search').value = 'Fort';

        // Act
        jQuery('#picker_title_search').trigger(jQuery.Event('keydown', {keyCode: 13}));

        // Assert
        expect(ajaxCalls).toHaveLength(3);
        expect(ajaxCalls[2].data.start).toBe(0);
        expect(ajaxCalls[2].data.columns[0].search.value).toBe('Fort');
    });

    it('dungeonFilter_givenAChangeBeforeTheDrawerWasEverShown_doesNotListYet', () => {
        // Act
        jQuery('#picker_dungeon').val('3').trigger('change');

        // Assert
        expect(ajaxCalls).toHaveLength(0);
    });

    it('reload_givenUnchangedFilters_listsTheRoutesAgain', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a')]);

        // Act
        picker.reload();

        // Assert
        expect(ajaxCalls).toHaveLength(2);
    });

    it('load_givenTheRequestFails_showsTheErrorState', () => {
        // Arrange
        picker.reload();

        // Act
        ajaxCalls[0].error({}, 'error');

        // Assert
        expect(document.querySelector('#picker_error').hidden).toBe(false);
        expect(document.querySelector('#picker_list').children).toHaveLength(0);
    });

    it('activate_givenQueryParametersNamedLikeAFilter_leavesTheFiltersAndTheUrlAlone', () => {
        // Arrange
        globalThis.getQueryParams = vi.fn(() => ({title: 'from the url'}));
        const pushState = vi.spyOn(history, 'pushState');

        // Act
        picker.reload();

        // Assert
        expect(globalThis.getQueryParams).not.toHaveBeenCalled();
        expect(pushState).not.toHaveBeenCalled();
        expect(ajaxCalls[0].data.columns[0].search.value).toBe('');
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
        expect(document.querySelector('#picker_confirm').textContent).toBe('Add 2 routes');
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

    it('onCheckboxChanged_givenADungeonReachesMaxPerDungeon_disablesOnlyThatDungeonsRows', () => {
        // Arrange - 'existing' is already in for dungeon 3, so one more route of dungeon 3 fits
        picker = new CommonDungeonroutePicker('picker', 'common/dungeonroute/picker', Object.assign({}, OPTIONS, {
            max:                null,
            maxPerDungeon:      2,
            existingDungeonIds: {existing: 3},
        }));
        picker.activate();
        picker.reload();
        const otherDungeon = {id: 4, name: 'dungeons.ara_kara', key: 'other', expansion: {shortname: 'tww'}};
        respondWithRoutes([route('a'), route('b'), route('c', {dungeon: otherDungeon})]);

        // Act
        tick('a');

        // Assert
        expect(picker.getRemainingForDungeon(3)).toBe(0);
        expect(rowOf('a').querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(rowOf('b').querySelector('.route_picker_checkbox').disabled).toBe(true);
        expect(rowOf('c').querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(document.querySelector('#picker_full').hidden).toBe(false);
        expect(document.querySelector('#picker_full').textContent).toBe('Dungeon limit is 2');
    });

    it('setExistingPublicKeys_givenARouteOfAFullDungeonLeaves_makesThatDungeonsRowsTickableAgain', () => {
        // Arrange
        picker = new CommonDungeonroutePicker('picker', 'common/dungeonroute/picker', Object.assign({}, OPTIONS, {
            max:                null,
            maxPerDungeon:      1,
            existingDungeonIds: {existing: 3},
        }));
        picker.activate();
        picker.reload();
        respondWithRoutes([route('a')]);

        // Act
        picker.setExistingPublicKeys([], {});

        // Assert
        expect(rowOf('a').querySelector('.route_picker_checkbox').disabled).toBe(false);
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
        expect(document.querySelector('#picker_confirm').disabled).toBe(true);
        expect(document.querySelector('#picker_confirm').textContent).toBe('Add routes');
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

    it('add_givenTicksFromAnEarlierPage_handsTheHostTheirRowsToo', () => {
        // Arrange
        document.body.innerHTML = MARKUP;
        picker = new CommonDungeonroutePicker('picker', 'common/dungeonroute/picker', Object.assign({}, OPTIONS, {actionUrl: null}));
        picker.activate();
        ajaxCalls.length = 0;
        picker.reload();
        respondWithRoutes([route('a'), route('b')], 4);
        tick('a');
        document.querySelector('#picker_next').click();
        respondWithRoutes([route('c'), route('d')], 4);
        tick('c');
        const callback = vi.fn();
        picker.onConfirmed(callback);

        // Act
        document.querySelector('#picker_confirm').click();

        // Assert
        expect(callback).toHaveBeenCalledWith({
            publicKeys: ['a', 'c'],
            dungeonRoutes: [expect.objectContaining({publicKey: 'a'}), expect.objectContaining({publicKey: 'c'})],
            response:   null,
        });
    });

    it('add_givenTickedRoutes_postsThemAndReportsTheResultToTheHost', () => {
        // Arrange
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);
        tick('b');
        tick('a');
        const callback = vi.fn();
        const eventHandler = vi.fn();
        picker.onConfirmed(callback);
        jQuery('#picker').on('dungeonroutepicker:confirmed', eventHandler);

        // Act
        document.querySelector('#picker_confirm').click();
        const post = ajaxCalls.find((call) => call.type === 'POST');
        post.success({added: 2});
        post.complete();

        // Assert
        expect(post.url).toBe('/target/add');
        expect(post.data).toEqual({dungeon_routes: ['b', 'a']});
        expect(callback).toHaveBeenCalledWith(expect.objectContaining({publicKeys: ['b', 'a'], response: {added: 2}}));
        expect(eventHandler.mock.calls[0][1]).toMatchObject({publicKeys: ['b', 'a'], response: {added: 2}});
        expect(offcanvas.hide).toHaveBeenCalledTimes(1);
        expect(picker.getSelectedPublicKeys()).toEqual([]);
        expect(rowOf('a').querySelector('.route_picker_already_in').hidden).toBe(false);
    });

    it('add_givenNoActionUrl_handsTheRoutesToTheHostWithoutPostingThem', () => {
        // Arrange
        // A clean DOM, so only the drawer under test is bound to it
        document.body.innerHTML = MARKUP;
        picker = new CommonDungeonroutePicker('picker', 'common/dungeonroute/picker', Object.assign({}, OPTIONS, {actionUrl: null}));
        picker.activate();
        ajaxCalls.length = 0;
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);
        tick('a');
        const callback = vi.fn();
        picker.onConfirmed(callback);

        // Act
        document.querySelector('#picker_confirm').click();

        // Assert
        expect(ajaxCalls.filter((call) => call.type === 'POST')).toHaveLength(0);
        expect(callback).toHaveBeenCalledWith({
            publicKeys: ['a'],
            dungeonRoutes: [expect.objectContaining({publicKey: 'a'})],
            response:   null,
        });
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
        document.querySelector('#picker_confirm').click();
        const post = ajaxCalls.find((call) => call.type === 'POST');
        post.error({status: 422}, 'error');
        post.complete();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a']);
        expect(document.querySelector('#picker_status').textContent).toBe('Adding failed');
        expect(document.querySelector('#picker_confirm').disabled).toBe(false);
        expect(offcanvas.hide).not.toHaveBeenCalled();
    });

    /**
     * A drawer in delete mode, bound to a clean DOM so only it is bound to the markup.
     * @returns {CommonDungeonroutePicker}
     */
    function deletePicker(overrides = {}) {
        document.body.innerHTML = MARKUP;
        const deleteDrawer = new CommonDungeonroutePicker('picker', 'common/dungeonroute/picker',
            Object.assign({}, OPTIONS, {
                actionKeyPrefix:    'dungeonroute_picker_delete',
                actionUrl:          '/ajax/routes',
                actionMethod:       'DELETE',
                confirmsAction:     true,
                removesActedRoutes: true,
                actedFieldName:     'dungeon_routes',
                selectPageSelector: '#picker_select_page',
                max:                null,
                existingPublicKeys: [],
            }, overrides));
        deleteDrawer.activate();
        ajaxCalls.length = 0;

        return deleteDrawer;
    }

    it('refreshSelection_givenDeleteMode_namesTheDeleteActionOnTheConfirmButton', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);

        // Act
        tick('a');
        tick('b');

        // Assert
        expect(document.querySelector('#picker_confirm').textContent).toBe('Delete 2 routes');
    });

    it('confirm_givenDeleteModeAndAConfirmedPrompt_sendsThemToTheDeleteEndpoint', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);
        tick('a');
        const callback = vi.fn();
        picker.onConfirmed(callback);

        // Act
        document.querySelector('#picker_confirm').click();
        globalThis.showConfirmYesCancel.mock.calls[0][1]();
        const request = ajaxCalls.find((call) => call.type === 'DELETE');
        request.success({dungeon_routes: ['a']});
        request.complete();

        // Assert
        expect(globalThis.showConfirmYesCancel).toHaveBeenCalledWith('Delete this route permanently?', expect.any(Function));
        expect(request.url).toBe('/ajax/routes');
        expect(request.data).toEqual({dungeon_routes: ['a']});
        expect(callback).toHaveBeenCalledWith(expect.objectContaining({publicKeys: ['a'], response: {dungeon_routes: ['a']}}));
        expect(offcanvas.hide).toHaveBeenCalledTimes(1);
    });

    it('confirm_givenDeleteModeAndADismissedPrompt_sendsNothing', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a')]);
        tick('a');

        // Act
        document.querySelector('#picker_confirm').click();

        // Assert
        expect(ajaxCalls.filter((call) => call.type === 'DELETE')).toHaveLength(0);
        expect(picker.getSelectedPublicKeys()).toEqual(['a']);
    });

    it('confirm_givenDeleteModeAndAFailingEndpoint_keepsTheSelectionAndSaysSo', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a')]);
        tick('a');

        // Act
        document.querySelector('#picker_confirm').click();
        globalThis.showConfirmYesCancel.mock.calls[0][1]();
        const request = ajaxCalls.find((call) => call.type === 'DELETE');
        request.error({status: 403}, 'error');
        request.complete();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a']);
        expect(document.querySelector('#picker_status').textContent).toBe('Deleting failed');
        expect(offcanvas.hide).not.toHaveBeenCalled();
    });

    it('confirm_givenDeleteModeAndAMax_doesNotLetDeletedRoutesEatTheNextBatchesAllowance', () => {
        // Arrange - a drawer that may only act on two routes at a time
        picker = deletePicker({max: 2});
        picker.reload();
        respondWithRoutes([route('a'), route('b')], 4);
        tick('a');
        tick('b');

        // Act - delete both, then open the drawer again for the next batch
        document.querySelector('#picker_confirm').click();
        globalThis.showConfirmYesCancel.mock.calls[0][1]();
        let request = ajaxCalls.find((call) => call.type === 'DELETE');
        request.success({dungeon_routes: ['a', 'b']});
        request.complete();
        jQuery('#picker').trigger('show.bs.offcanvas');
        respondWithRoutes([route('c'), route('d')], 2);

        // Assert - the whole allowance is available again, and the new page is tickable
        expect(picker.getRemaining()).toBe(2);
        expect(rowOf('c').querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(rowOf('d').querySelector('.route_picker_checkbox').disabled).toBe(false);
    });

    it('confirm_givenTheServerActedOnFewerRoutesThanSent_keepsTheRestTickedAndStaysOpen', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a'), route('b')], 2);
        tick('a');
        tick('b');
        const callback = vi.fn();
        picker.onConfirmed(callback);

        // Act - the endpoint reports it only got as far as the first route
        document.querySelector('#picker_confirm').click();
        globalThis.showConfirmYesCancel.mock.calls[0][1]();
        const request = ajaxCalls.find((call) => call.type === 'DELETE');
        request.success({dungeon_routes: ['a']});
        request.complete();

        // Assert
        expect(callback).toHaveBeenCalledWith(expect.objectContaining({publicKeys: ['a']}));
        expect(picker.getSelectedPublicKeys()).toEqual(['b']);
        expect(document.querySelector('#picker_status').textContent).toBe('Deleting failed');
        expect(offcanvas.hide).not.toHaveBeenCalled();
    });

    it('confirm_givenTheServerActedOnNoRouteAtAll_reportsNothingToTheHost', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a')], 1);
        tick('a');
        const callback = vi.fn();
        picker.onConfirmed(callback);

        // Act
        document.querySelector('#picker_confirm').click();
        globalThis.showConfirmYesCancel.mock.calls[0][1]();
        const request = ajaxCalls.find((call) => call.type === 'DELETE');
        request.success({dungeon_routes: []});
        request.complete();

        // Assert
        expect(callback).not.toHaveBeenCalled();
        expect(picker.getSelectedPublicKeys()).toEqual(['a']);
        expect(document.querySelector('#picker_status').textContent).toBe('Deleting failed');
    });

    /**
     * @returns {HTMLInputElement}
     */
    function selectPageBox() {
        return document.querySelector('#picker_select_page');
    }

    it('selectPage_givenALoadedPage_ticksEveryRouteOnIt', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);

        // Act
        selectPageBox().click();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a', 'b']);
        expect(selectPageBox().checked).toBe(true);
        expect(selectPageBox().indeterminate).toBe(false);
        expect(rowOf('a').querySelector('.route_picker_checkbox').checked).toBe(true);
        expect(document.querySelector('#picker_confirm').textContent).toBe('Delete 2 routes');
        expect(document.querySelector('#picker_status').textContent).toBe('2 selected');
    });

    it('selectPage_givenAMaxSmallerThanThePage_ticksInListedOrderUntilFull', () => {
        // Arrange
        picker = deletePicker({max: 2});
        picker.reload();
        respondWithRoutes([route('a'), route('b'), route('c')]);

        // Act
        selectPageBox().click();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a', 'b']);
        expect(selectPageBox().checked).toBe(false);
        expect(selectPageBox().indeterminate).toBe(true);
        expect(selectPageBox().disabled).toBe(false);
        expect(rowOf('c').querySelector('.route_picker_checkbox').disabled).toBe(true);
        expect(document.querySelector('#picker_full').hidden).toBe(false);
    });

    it('refreshSelection_givenDeleteModeAtTheMax_namesTheDeleteLimit', () => {
        // Arrange
        picker = deletePicker({max: 1});
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);

        // Act
        tick('a');

        // Assert
        expect(document.querySelector('#picker_full').hidden).toBe(false);
        expect(document.querySelector('#picker_full').textContent).toBe('Delete at most 1');
    });

    it('selectPage_givenAFullPartlyTickedPage_unticksThePage', () => {
        // Arrange
        picker = deletePicker({max: 2});
        picker.reload();
        respondWithRoutes([route('a'), route('b'), route('c')]);
        selectPageBox().click();

        // Act
        selectPageBox().click();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual([]);
        expect(selectPageBox().checked).toBe(false);
        expect(selectPageBox().indeterminate).toBe(false);
        expect(rowOf('c').querySelector('.route_picker_checkbox').disabled).toBe(false);
        expect(document.querySelector('#picker_status').textContent).toBe('None selected');
    });

    it('selectPage_givenAPartlyTickedPage_ticksTheRest', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a'), route('b'), route('c')]);
        tick('b');

        // Act
        selectPageBox().click();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['b', 'a', 'c']);
        expect(selectPageBox().checked).toBe(true);
    });

    it('selectPage_givenATickedRow_showsThePageAsPartlyTicked', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a'), route('b')]);

        // Act
        tick('a');

        // Assert
        expect(selectPageBox().checked).toBe(false);
        expect(selectPageBox().indeterminate).toBe(true);
    });

    it('selectPage_givenRoutesAlreadyInTheTarget_skipsThem', () => {
        // Arrange
        picker = deletePicker({existingPublicKeys: ['x']});
        picker.reload();
        respondWithRoutes([route('x'), route('a')]);

        // Act
        selectPageBox().click();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a']);
        expect(selectPageBox().checked).toBe(true);
    });

    it('selectPage_givenTicksFromAnotherPage_unticksOnlyThisPage', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        respondWithRoutes([route('a'), route('b')], 4);
        selectPageBox().click();
        document.querySelector('#picker_next').click();
        respondWithRoutes([route('c'), route('d')], 4);
        selectPageBox().click();

        // Act
        selectPageBox().click();

        // Assert
        expect(picker.getSelectedPublicKeys()).toEqual(['a', 'b']);
        expect(selectPageBox().checked).toBe(false);
    });

    it('selectPage_givenTheMaxWasReachedOnAnotherPage_isDisabled', () => {
        // Arrange
        picker = deletePicker({max: 2});
        picker.reload();
        respondWithRoutes([route('a'), route('b')], 4);
        selectPageBox().click();

        // Act
        document.querySelector('#picker_next').click();
        respondWithRoutes([route('c'), route('d')], 4);

        // Assert
        expect(selectPageBox().checked).toBe(false);
        expect(selectPageBox().indeterminate).toBe(false);
        expect(selectPageBox().disabled).toBe(true);
    });

    it('selectPage_givenTheListIsLoadingOrEmpty_isHidden', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();
        const box = document.querySelector('.route_picker_select_page');
        const hiddenWhileLoading = box.hidden;

        // Act
        respondWithRoutes([]);

        // Assert
        expect(hiddenWhileLoading).toBe(true);
        expect(box.hidden).toBe(true);
    });

    it('selectPage_givenALoadedPage_isShown', () => {
        // Arrange
        picker = deletePicker();
        picker.reload();

        // Act
        respondWithRoutes([route('a')]);

        // Assert
        expect(document.querySelector('.route_picker_select_page').hidden).toBe(false);
    });

    it('selectPage_givenAddMode_staysHidden', () => {
        // Arrange - the add drawer has no selectPageSelector

        // Act
        picker.reload();
        respondWithRoutes([route('a')]);

        // Assert
        expect(document.querySelector('.route_picker_select_page').hidden).toBe(true);
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
