const {DungeonSheet, dungeonSheetRowMatches} = require('./dungeonsheet');

describe('dungeonSheetRowMatches', () => {
    it.each([
        ['an abbreviation', true, 'blackrock depths brd', 'brd'],
        ['a part of the name', true, 'blackrock depths brd', 'depth'],
        ['terms in any order', true, 'scarlet monastery library sml', 'lib scar'],
        ['different case', true, 'blackrock depths brd', 'BRD'],
        ['an unaccented query for an accented name', true, 'ahn\'qiraj aq40 caña', 'cana'],
        ['an empty query', true, 'blackrock depths brd', '  '],
        ['a term that occurs nowhere', false, 'blackrock depths brd', 'molten'],
        ['one of two terms missing', false, 'scarlet monastery library sml', 'scar armory'],
    ])('given %s returns %s', (_, expected, filterText, query) => {
        // Act
        const matches = dungeonSheetRowMatches(filterText, query);

        // Assert
        expect(matches).toBe(expected);
    });
});

describe('DungeonSheet', () => {
    const originalMatchMedia = window.matchMedia;
    const originalBootstrap = globalThis.bootstrap;
    let mediaQueryListeners;
    let hiddenOffcanvases;
    let shownOffcanvases;
    let isDesktop;

    beforeEach(() => {
        mediaQueryListeners = [];
        hiddenOffcanvases = [];
        shownOffcanvases = [];
        isDesktop = false;
        sessionStorage.clear();
        window.matchMedia = query => ({
            media: query,
            matches: isDesktop,
            addEventListener: (type, listener) => mediaQueryListeners.push({query, type, listener}),
        });
        globalThis.bootstrap = {
            Offcanvas: {
                getInstance: element => ({hide: () => hiddenOffcanvases.push(element)}),
                getOrCreateInstance: element => ({show: () => shownOffcanvases.push(element)}),
            },
        };
    });

    afterEach(() => {
        window.matchMedia = originalMatchMedia;
        globalThis.bootstrap = originalBootstrap;
        sessionStorage.clear();
        vi.useRealTimers();
    });

    /**
     * @param {boolean} matches
     */
    function crossBreakpoint(matches) {
        mediaQueryListeners
            .filter(({query, type}) => query === DungeonSheet.DESKTOP_MEDIA_QUERY && type === 'change')
            .forEach(({listener}) => listener({matches}));
    }

    /**
     * Two groups, the selected dungeon in the first - the markup of common/layout/nav/dungeoncontext, trimmed.
     *
     * @returns {DungeonSheet}
     */
    function makeSheet() {
        document.body.innerHTML = `
            <header id="site_header">
                <div class="offcanvas offcanvas-bottom dungeon_sheet" id="dungeon_sheet">
                    <div class="offcanvas-body dungeon_sheet_body">
                        <nav class="game_version_segments">
                            <a class="game_version_segment border-accent" id="retail" href="#retail" data-current="true" aria-current="true">Retail</a>
                            <a class="game_version_segment" id="classic" href="#classic" data-current="false">Classic Era</a>
                        </nav>
                        <input type="search" class="dungeon_sheet_filter_input"/>
                        <div class="dungeon_sheet_list">
                            <section class="dungeon_sheet_group" id="group_dungeon">
                                <ul class="dungeon_sheet_rows">
                                    <li><a class="dungeon_sheet_row" id="brd" href="#brd" data-filter-text="blackrock depths brd">Blackrock Depths</a></li>
                                    <li><a class="dungeon_sheet_row" id="dm" href="#dm" data-filter-text="the deadmines dm" aria-current="true">The Deadmines</a></li>
                                </ul>
                            </section>
                            <section class="dungeon_sheet_group" id="group_raid">
                                <ul class="dungeon_sheet_rows">
                                    <li><a class="dungeon_sheet_row" id="mc" href="#mc" data-filter-text="molten core mc">Molten Core</a></li>
                                </ul>
                            </section>
                            <p class="dungeon_sheet_empty" hidden>No dungeon matches that filter.</p>
                        </div>
                    </div>
                </div>
            </header>`;

        return new DungeonSheet(document.getElementById('dungeon_sheet'));
    }

    /**
     * @returns {string[]}
     */
    function visibleRowIds(sheet) {
        return sheet.visibleRows().map(row => row.id);
    }

    it('constructor_givenASheetInsideTheHeader_movesItToTheEndOfTheBody', () => {
        // Act
        const sheet = makeSheet();

        // Assert
        expect(sheet.element.parentElement).toBe(document.body);
        expect(document.body.lastElementChild).toBe(sheet.element);
    });

    it('filter_givenAnAbbreviation_hidesEveryOtherRowAndTheGroupItEmptied', () => {
        // Arrange
        const sheet = makeSheet();

        // Act
        sheet.filter('mc');

        // Assert
        expect(visibleRowIds(sheet)).toEqual(['mc']);
        expect(document.getElementById('group_dungeon').hidden).toBe(true);
        expect(document.getElementById('group_raid').hidden).toBe(false);
        expect(document.querySelector('.dungeon_sheet_empty').hidden).toBe(true);
    });

    it('filter_givenNoMatch_showsTheEmptyState', () => {
        // Arrange
        const sheet = makeSheet();

        // Act
        sheet.filter('zzq');

        // Assert
        expect(visibleRowIds(sheet)).toEqual([]);
        expect(document.getElementById('group_dungeon').hidden).toBe(true);
        expect(document.getElementById('group_raid').hidden).toBe(true);
        expect(document.querySelector('.dungeon_sheet_empty').hidden).toBe(false);
    });

    it('filter_givenAClearedQuery_showsEveryRowAgain', () => {
        // Arrange
        const sheet = makeSheet();
        sheet.filter('zzq');

        // Act
        sheet.filter('');

        // Assert
        expect(visibleRowIds(sheet)).toEqual(['brd', 'dm', 'mc']);
        expect(document.getElementById('group_dungeon').hidden).toBe(false);
        expect(document.querySelector('.dungeon_sheet_empty').hidden).toBe(true);
    });

    it('input_givenTyping_filtersTheRows', () => {
        // Arrange
        const sheet = makeSheet();

        // Act
        sheet.input.value = 'dead';
        sheet.input.dispatchEvent(new Event('input'));

        // Assert
        expect(visibleRowIds(sheet)).toEqual(['dm']);
    });

    it('input_givenEnter_followsTheFirstRowLeft', () => {
        // Arrange
        const sheet = makeSheet();
        const clicked = [];
        sheet.rows.forEach(row => row.addEventListener('click', event => {
            event.preventDefault();
            clicked.push(row.id);
        }));
        sheet.filter('core');

        // Act
        const event = new KeyboardEvent('keydown', {key: 'Enter', cancelable: true});
        sheet.input.dispatchEvent(event);

        // Assert
        expect(clicked).toEqual(['mc']);
        expect(event.defaultPrevented).toBe(true);
    });

    it('input_givenEnterWithNoRowLeft_followsNothing', () => {
        // Arrange
        const sheet = makeSheet();
        const clicked = [];
        sheet.rows.forEach(row => row.addEventListener('click', event => {
            event.preventDefault();
            clicked.push(row.id);
        }));
        sheet.filter('zzq');

        // Act
        sheet.input.dispatchEvent(new KeyboardEvent('keydown', {key: 'Enter', cancelable: true}));

        // Assert
        expect(clicked).toEqual([]);
    });

    it('input_givenEnterDuringAnImeComposition_followsNothing', () => {
        // Arrange
        const sheet = makeSheet();
        const clicked = [];
        sheet.rows.forEach(row => row.addEventListener('click', event => {
            event.preventDefault();
            clicked.push(row.id);
        }));
        sheet.filter('core');

        // Act
        const event = new KeyboardEvent('keydown', {key: 'Enter', isComposing: true, cancelable: true});
        sheet.input.dispatchEvent(event);

        // Assert
        expect(clicked).toEqual([]);
        expect(event.defaultPrevented).toBe(false);
    });

    it('breakpoint_givenTheViewportGrowsToDesktop_hidesTheSheet', () => {
        // Arrange
        const sheet = makeSheet();

        // Act
        crossBreakpoint(true);

        // Assert
        expect(hiddenOffcanvases).toEqual([sheet.element]);
    });

    it('breakpoint_givenTheViewportShrinksToMobile_leavesTheSheetAlone', () => {
        // Arrange
        makeSheet();

        // Act
        crossBreakpoint(false);

        // Assert
        expect(mediaQueryListeners).toHaveLength(1);
        expect(hiddenOffcanvases).toEqual([]);
    });

    /**
     * @param {string} id
     * @param {MouseEventInit} init
     * @returns {MouseEvent}
     */
    function clickGameVersion(id, init = {}) {
        const event = new MouseEvent('click', {bubbles: true, cancelable: true, button: 0, ...init});
        // jsdom would follow the href and stop the test run
        document.getElementById(id).addEventListener('click', clickEvent => clickEvent.preventDefault(), {once: true});
        document.getElementById(id).dispatchEvent(event);

        return event;
    }

    it('gameVersionClick_givenAnotherVersion_marksItSelectedAndDimsTheList', () => {
        // Arrange
        const sheet = makeSheet();

        // Act
        clickGameVersion('classic');

        // Assert
        expect(document.getElementById('classic').getAttribute('aria-current')).toBe('true');
        expect(document.getElementById('classic').classList.contains('border-accent')).toBe(true);
        expect(document.getElementById('retail').hasAttribute('aria-current')).toBe(false);
        expect(document.getElementById('retail').classList.contains('border-accent')).toBe(false);
        expect(sheet.element.classList.contains('is-switching')).toBe(true);
        expect(sheet.body.getAttribute('aria-busy')).toBe('true');
    });

    it('gameVersionClick_givenAnotherVersion_asksTheNextPageToReopenTheSheet', () => {
        // Arrange
        vi.useFakeTimers({now: 1_000_000, toFake: ['Date']});
        makeSheet();

        // Act
        clickGameVersion('classic');

        // Assert
        expect(sessionStorage.getItem(DungeonSheet.REOPEN_STORAGE_KEY)).toBe('1000000');
    });

    it.each([
        ['ACtrl', {ctrlKey: true}],
        ['AMeta', {metaKey: true}],
        ['AShift', {shiftKey: true}],
        ['AnAlt', {altKey: true}],
        ['AMiddleButton', {button: 1}],
    ])('gameVersionClick_given%sClick_leavesTheSheetAsItIs', (_, init) => {
        // Arrange
        const sheet = makeSheet();

        // Act
        clickGameVersion('classic', init);

        // Assert
        expect(sessionStorage.getItem(DungeonSheet.REOPEN_STORAGE_KEY)).toBeNull();
        expect(sheet.element.classList.contains('is-switching')).toBe(false);
        expect(document.getElementById('retail').getAttribute('aria-current')).toBe('true');
    });

    it('gameVersionClick_givenTheCurrentVersion_leavesTheSheetAsItIs', () => {
        // Arrange
        const sheet = makeSheet();

        // Act
        clickGameVersion('retail');

        // Assert
        expect(sessionStorage.getItem(DungeonSheet.REOPEN_STORAGE_KEY)).toBeNull();
        expect(sheet.element.classList.contains('is-switching')).toBe(false);
    });

    it('constructor_givenARecentVersionSwitch_reopensTheSheetAndForgetsTheSwitch', () => {
        // Arrange
        vi.useFakeTimers({now: 1_000_000, toFake: ['Date']});
        sessionStorage.setItem(DungeonSheet.REOPEN_STORAGE_KEY, `${1_000_000 - DungeonSheet.REOPEN_MAX_AGE_MS}`);

        // Act
        const sheet = makeSheet();

        // Assert
        expect(shownOffcanvases).toEqual([sheet.element]);
        expect(sessionStorage.getItem(DungeonSheet.REOPEN_STORAGE_KEY)).toBeNull();
    });

    it('constructor_givenAnAbandonedVersionSwitch_leavesTheSheetClosedAndForgetsTheSwitch', () => {
        // Arrange
        vi.useFakeTimers({now: 1_000_000, toFake: ['Date']});
        sessionStorage.setItem(DungeonSheet.REOPEN_STORAGE_KEY, `${1_000_000 - DungeonSheet.REOPEN_MAX_AGE_MS - 1}`);

        // Act
        makeSheet();

        // Assert
        expect(shownOffcanvases).toEqual([]);
        expect(sessionStorage.getItem(DungeonSheet.REOPEN_STORAGE_KEY)).toBeNull();
    });

    it('constructor_givenAVersionSwitchOnDesktop_leavesTheSheetClosedAndForgetsTheSwitch', () => {
        // Arrange
        isDesktop = true;
        sessionStorage.setItem(DungeonSheet.REOPEN_STORAGE_KEY, `${Date.now()}`);

        // Act
        makeSheet();

        // Assert
        expect(shownOffcanvases).toEqual([]);
        expect(sessionStorage.getItem(DungeonSheet.REOPEN_STORAGE_KEY)).toBeNull();
    });

    it('constructor_givenNoVersionSwitch_leavesTheSheetClosed', () => {
        // Act
        makeSheet();

        // Assert
        expect(shownOffcanvases).toEqual([]);
    });

    it('pageshow_givenARestoreFromTheBackForwardCache_endsThePendingSwitch', () => {
        // Arrange
        const sheet = makeSheet();
        clickGameVersion('classic');

        // Act
        window.dispatchEvent(new PageTransitionEvent('pageshow', {persisted: true}));

        // Assert
        expect(sheet.element.classList.contains('is-switching')).toBe(false);
        expect(sheet.body.hasAttribute('aria-busy')).toBe(false);
        expect(document.getElementById('retail').getAttribute('aria-current')).toBe('true');
        expect(document.getElementById('retail').classList.contains('border-accent')).toBe(true);
        expect(document.getElementById('classic').hasAttribute('aria-current')).toBe(false);
        expect(document.getElementById('classic').classList.contains('border-accent')).toBe(false);
    });

    it('pageshow_givenAFreshLoad_keepsThePendingSwitch', () => {
        // Arrange
        const sheet = makeSheet();
        clickGameVersion('classic');

        // Act
        window.dispatchEvent(new PageTransitionEvent('pageshow', {persisted: false}));

        // Assert
        expect(sheet.element.classList.contains('is-switching')).toBe(true);
    });

    it('hidden_givenAFilter_clearsItAndShowsEveryRowAgain', () => {
        // Arrange
        const sheet = makeSheet();
        sheet.input.value = 'mc';
        sheet.filter('mc');

        // Act
        sheet.element.dispatchEvent(new Event('hidden.bs.offcanvas'));

        // Assert
        expect(sheet.input.value).toBe('');
        expect(visibleRowIds(sheet)).toEqual(['brd', 'dm', 'mc']);
    });

    it('show_givenASelectedDungeonBelowTheFold_centresItInTheList', () => {
        // Arrange
        const sheet = makeSheet();
        const selected = document.getElementById('dm');
        sheet.body.getBoundingClientRect = () => ({top: 100, height: 400});
        selected.getBoundingClientRect = () => ({top: 800, height: 44});

        // Act
        sheet.element.dispatchEvent(new Event('show.bs.offcanvas'));

        // Assert
        expect(sheet.body.scrollTop).toBe(800 - 100 - (400 - 44) / 2);
    });
});
