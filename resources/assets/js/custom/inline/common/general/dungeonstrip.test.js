const {DungeonStrip} = require('./dungeonstrip');
const {DungeonSheet} = require('./dungeonsheet');

describe('DungeonStrip', () => {
    const messages = {
        'js.dungeon_strip_most_viewed': 'Most viewed',
        'js.dungeon_strip_not_viewed': 'No recent views',
        'js.dungeon_strip_view_share': ':percent% of top views',
    };
    const originalLang = globalThis.lang;
    const originalDungeonSheet = globalThis.DungeonSheet;

    beforeEach(() => {
        // The strip filters with the mobile sheet's matcher, which the bundle has loaded alongside it
        globalThis.DungeonSheet = DungeonSheet;
        globalThis.lang = {
            get: (key, replacements = {}) => Object.entries(replacements)
                .reduce((message, [name, value]) => message.replace(`:${name}`, value), messages[key] ?? key),
        };
    });

    afterEach(() => {
        globalThis.lang = originalLang;
        globalThis.DungeonSheet = originalDungeonSheet;
        vi.useRealTimers();
    });

    /**
     * Two groups - four dungeons and two raids, one without view counts - and the selected dungeon in the readout:
     * the markup of common/dungeon/list/chips, trimmed.
     *
     * @param {string|null} selected The aria-label of the chip to mark current
     * @returns {DungeonStrip}
     */
    function makeStrip(selected = null) {
        document.body.innerHTML = `
            <div class="dungeon_strip">
                <div class="dungeon_strip_readout" data-name="Blackrock Depths" data-image="http://test/brd.webp" data-view-share="1">
                    <img class="dungeon_strip_readout_image" src="http://test/brd.webp" alt=""/>
                    <span class="dungeon_strip_readout_text">
                        <span class="dungeon_strip_readout_name">Blackrock Depths</span>
                        <span class="dungeon_strip_readout_views">Most viewed</span>
                    </span>
                </div>
                <button type="button" class="dungeon_strip_search" aria-expanded="false" aria-label="Filter dungeons">S</button>
                <div class="dungeon_strip_groups" id="dungeon_strip_groups">
                    <div class="dungeon_strip_filter" role="search">
                        <input type="search" class="form-control dungeon_strip_filter_input" aria-label="Filter dungeons"/>
                    </div>
                    <div class="dungeon_strip_group dungeon_strip_group--dungeon" role="group">
                        <div class="dungeon_strip_chips">
                            <a class="dungeon_strip_chip" href="/brd" aria-label="Blackrock Depths" data-image="http://test/brd.webp" data-view-share="1">BRD</a>
                            <a class="dungeon_strip_chip" href="/sm" aria-label="Scarlet Monastery" data-image="http://test/sm.webp">SM</a>
                            <a class="dungeon_strip_chip" href="/st" aria-label="Temple of Atal'Hakkar" data-image="http://test/st.webp">ST</a>
                            <a class="dungeon_strip_chip" href="/strat" aria-label="Stratholme" data-image="http://test/strat.webp">Strat</a>
                        </div>
                    </div>
                    <div class="dungeon_strip_group dungeon_strip_group--raid" role="group">
                        <div class="dungeon_strip_chips">
                            <a class="dungeon_strip_chip" href="/mc" aria-label="Molten Core" data-image="http://test/mc.webp" data-view-share="0.4133">MC</a>
                            <a class="dungeon_strip_chip" href="/zg" aria-label="Zul'Gurub" data-image="http://test/zg.webp">ZG</a>
                        </div>
                    </div>
                    <p class="dungeon_strip_filter_empty" hidden>No dungeon matches that filter.</p>
                </div>
                <button type="button" class="dungeon_strip_all" aria-expanded="false">All 6</button>
            </div>
            <a id="outside" href="/elsewhere">Elsewhere</a>
            <input id="outside_input" type="text"/>`;

        if (selected !== null) {
            chip(selected).setAttribute('aria-current', 'true');
        }

        return new DungeonStrip(document.querySelector('.dungeon_strip'));
    }

    /**
     * jsdom lays nothing out, so every chip is placed by hand the way the closed strip flows them: down two-row
     * columns, so the markup order (BRD, SM, ST, Strat) is not the reading order (BRD, ST / SM, Strat).
     *
     *   BRD  ST   |  MC  ZG
     *   SM   Strat|
     *
     * The groups are wide enough for all of them, so focusing a chip does not unfold the strip.
     */
    function layOutGrid() {
        document.querySelector('.dungeon_strip_groups').getBoundingClientRect = () => ({left: 0, top: 0, right: 400, bottom: 56, width: 400, height: 56, x: 0, y: 0});

        const positions = {
            'Blackrock Depths': [0, 0],
            'Scarlet Monastery': [0, 1],
            "Temple of Atal'Hakkar": [1, 0],
            'Stratholme': [1, 1],
            'Molten Core': [3, 0],
            "Zul'Gurub": [4, 0],
        };

        for (const [label, [column, row]] of Object.entries(positions)) {
            const left = column * 50;
            const top = row * 30;
            chip(label).getBoundingClientRect = () => ({left, top, right: left + 46, bottom: top + 26, width: 46, height: 26, x: left, y: top});
        }
    }

    /**
     * @param {HTMLElement} target
     * @param {string} key
     * @returns {KeyboardEvent}
     */
    function press(target, key) {
        const event = new KeyboardEvent('keydown', {key, bubbles: true, cancelable: true});
        target.dispatchEvent(event);

        return event;
    }

    /**
     * jsdom has no layout, so the strip reports no boxes - as it does below the lg breakpoint - unless given one.
     *
     * @param {DungeonStrip} strip
     */
    function showStrip(strip) {
        strip.element.getClientRects = () => [{left: 0, top: 0, width: 800, height: 64}];
    }

    /**
     * @returns {string[]}
     */
    function tabStops() {
        return [...document.querySelectorAll('.dungeon_strip_chip')]
            .filter(element => element.tabIndex === 0)
            .map(element => element.getAttribute('aria-label'));
    }

    /**
     * @returns {string[]}
     */
    function shownChips() {
        return [...document.querySelectorAll('.dungeon_strip_chip')]
            .filter(element => !element.hidden && !element.closest('.dungeon_strip_group').hidden)
            .map(element => element.getAttribute('aria-label'));
    }

    /**
     * @returns {{name: string, image: string}}
     */
    function readout() {
        return {
            name: document.querySelector('.dungeon_strip_readout_name').textContent,
            image: document.querySelector('.dungeon_strip_readout_image').getAttribute('src'),
        };
    }

    /**
     * @returns {string}
     */
    function readoutViews() {
        return document.querySelector('.dungeon_strip_readout_views').textContent;
    }

    /**
     * @param {string} label
     * @returns {HTMLElement}
     */
    function chip(label) {
        return document.querySelector(`.dungeon_strip_chip[aria-label="${label}"]`);
    }

    it('pointerover_givenAChip_namesItsDungeonInTheReadout', () => {
        // Arrange
        makeStrip();

        // Act
        chip('Molten Core').dispatchEvent(new Event('pointerover', {bubbles: true}));

        // Assert
        expect(readout()).toEqual({name: 'Molten Core', image: 'http://test/mc.webp'});
    });

    it('pointerleave_givenNoChipFocused_restoresTheSelectedDungeon', () => {
        // Arrange
        const strip = makeStrip();
        chip('Molten Core').dispatchEvent(new Event('pointerover', {bubbles: true}));

        // Act
        strip.element.dispatchEvent(new Event('pointerleave'));

        // Assert
        expect(readout()).toEqual({name: 'Blackrock Depths', image: 'http://test/brd.webp'});
    });

    it('focus_givenAChip_namesItsDungeonUntilFocusLeavesTheStrip', () => {
        // Arrange
        makeStrip();

        // Act
        chip('Molten Core').focus();
        const whileFocused = readout().name;
        document.getElementById('outside').focus();

        // Assert
        expect(whileFocused).toBe('Molten Core');
        expect(readout().name).toBe('Blackrock Depths');
    });

    it('pointerover_givenAChipWithViews_putsItsViewsInTheReadout', () => {
        // Arrange
        makeStrip();

        // Act
        chip('Molten Core').dispatchEvent(new Event('pointerover', {bubbles: true}));

        // Assert
        expect(readoutViews()).toBe('41% of top views');
    });

    it('pointerover_givenAChipWithoutViews_emptiesTheReadoutViews', () => {
        // Arrange
        makeStrip();

        // Act
        chip("Zul'Gurub").dispatchEvent(new Event('pointerover', {bubbles: true}));

        // Assert
        expect(readout().name).toBe("Zul'Gurub");
        expect(readoutViews()).toBe('');
    });

    it('pointerleave_givenNoChipFocused_restoresTheSelectedDungeonsViews', () => {
        // Arrange
        const strip = makeStrip();
        chip('Molten Core').dispatchEvent(new Event('pointerover', {bubbles: true}));

        // Act
        strip.element.dispatchEvent(new Event('pointerleave'));

        // Assert
        expect(readoutViews()).toBe('Most viewed');
    });

    it.each([
        [1, 'Most viewed'],
        [0, 'No recent views'],
        [0.4567, '46% of top views'],
        [0.001, '1% of top views'],
        [0.999, '99% of top views'],
    ])('describeViewShare_givenShare%s_returns%s', (viewShare, expected) => {
        // Act
        const result = DungeonStrip.describeViewShare(viewShare);

        // Assert
        expect(result).toBe(expected);
    });

    it('allButtonClick_givenAClosedStrip_opensAndClosesTheFlyout', () => {
        // Arrange
        const strip = makeStrip();
        const button = document.querySelector('.dungeon_strip_all');

        // Act
        button.click();
        const afterOpen = [strip.element.classList.contains('is-open'), button.getAttribute('aria-expanded')];
        button.click();

        // Assert
        expect(afterOpen).toEqual([true, 'true']);
        expect([strip.element.classList.contains('is-open'), button.getAttribute('aria-expanded')]).toEqual([false, 'false']);
    });

    it('allButtonClick_givenAKeyboardClick_focusesTheSelectedChip', () => {
        // Arrange
        const strip = makeStrip();
        chip('Molten Core').setAttribute('aria-current', 'true');
        const button = document.querySelector('.dungeon_strip_all');
        button.focus();

        // Act
        button.dispatchEvent(new MouseEvent('click', {bubbles: true, detail: 0}));

        // Assert
        expect(strip.isOpen()).toBe(true);
        expect(document.activeElement).toBe(chip('Molten Core'));
    });

    it('allButtonClick_givenAKeyboardClickWithoutASelectedChip_focusesTheFirstChip', () => {
        // Arrange
        const strip = makeStrip();
        const button = document.querySelector('.dungeon_strip_all');
        button.focus();

        // Act
        button.dispatchEvent(new MouseEvent('click', {bubbles: true, detail: 0}));

        // Assert
        expect(strip.isOpen()).toBe(true);
        expect(document.activeElement).toBe(chip('Blackrock Depths'));
    });

    it('allButtonClick_givenAPointerClick_leavesFocusOnTheButton', () => {
        // Arrange
        const strip = makeStrip();
        const button = document.querySelector('.dungeon_strip_all');
        button.focus();

        // Act
        button.dispatchEvent(new MouseEvent('click', {bubbles: true, detail: 1}));

        // Assert
        expect(strip.isOpen()).toBe(true);
        expect(document.activeElement).toBe(button);
    });

    /**
     * jsdom lays nothing out, so the name's line-clamped box reports the overflow it is given.
     *
     * @param {HTMLElement} name
     * @param {number} scrollHeight
     * @param {number} clientHeight
     */
    function setNameHeights(name, scrollHeight, clientHeight) {
        Object.defineProperty(name, 'scrollHeight', {configurable: true, get: () => scrollHeight});
        Object.defineProperty(name, 'clientHeight', {configurable: true, get: () => clientHeight});
    }

    it('fitReadoutName_givenANameOverflowingTwoLines_marksItLong', () => {
        // Arrange
        const strip = makeStrip();
        setNameHeights(strip.readoutName, 55, 37);

        // Act
        strip.fitReadoutName();

        // Assert
        expect(strip.readoutName.classList.contains('dungeon_strip_readout_name--long')).toBe(true);
    });

    it('fitReadoutName_givenALongNameReplacedByOneThatFits_unmarksIt', () => {
        // Arrange
        const strip = makeStrip();
        strip.readoutName.classList.add('dungeon_strip_readout_name--long');
        setNameHeights(strip.readoutName, 37, 37);

        // Act
        strip.fitReadoutName();

        // Assert
        expect(strip.readoutName.classList.contains('dungeon_strip_readout_name--long')).toBe(false);
    });

    it('pointerover_givenAChipWithALongName_marksTheReadoutNameLong', () => {
        // Arrange
        const strip = makeStrip();
        setNameHeights(strip.readoutName, 55, 37);

        // Act
        chip('Molten Core').dispatchEvent(new Event('pointerover', {bubbles: true}));

        // Assert
        expect(strip.readoutName.classList.contains('dungeon_strip_readout_name--long')).toBe(true);
    });

    it('escape_givenAnOpenFlyout_closesItAndFocusesTheAllButton', () => {
        // Arrange
        const strip = makeStrip();
        const button = document.querySelector('.dungeon_strip_all');
        button.click();
        chip('Molten Core').focus();

        // Act
        chip('Molten Core').dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));

        // Assert
        expect(strip.isOpen()).toBe(false);
        expect(document.activeElement).toBe(button);
    });

    it('pointerdown_givenAnOpenFlyoutAndAClickOutside_closesIt', () => {
        // Arrange
        const strip = makeStrip();
        document.querySelector('.dungeon_strip_all').click();

        // Act
        document.getElementById('outside').dispatchEvent(new Event('pointerdown', {bubbles: true}));

        // Assert
        expect(strip.isOpen()).toBe(false);
    });

    it('pointerdown_givenAnOpenFlyoutAndAClickInside_keepsItOpen', () => {
        // Arrange
        const strip = makeStrip();
        document.querySelector('.dungeon_strip_all').click();

        // Act
        chip('Molten Core').dispatchEvent(new Event('pointerdown', {bubbles: true}));

        // Assert
        expect(strip.isOpen()).toBe(true);
    });

    /**
     * jsdom does no layout, so the groups' widths are pinned by hand.
     *
     * @param {DungeonStrip} strip
     * @param {number} scrollWidth
     * @param {number} clientWidth
     */
    function setGroupsWidth(strip, scrollWidth, clientWidth) {
        Object.defineProperty(strip.groups, 'scrollWidth', {configurable: true, value: scrollWidth});
        Object.defineProperty(strip.groups, 'clientWidth', {configurable: true, value: clientWidth});
    }

    it('updateCompact_givenAListWiderThanTheStripInAShrunkHeader_compactsIt', () => {
        // Arrange
        const strip = makeStrip();
        const header = document.createElement('div');
        header.className = 'ksg-header ksg-header--shrink';
        strip.element.replaceWith(header);
        header.appendChild(strip.element);
        setGroupsWidth(strip, 900, 600);

        // Act
        strip.updateCompact();

        // Assert
        expect(strip.element.classList.contains('dungeon_strip--compact')).toBe(true);
    });

    it('updateCompact_givenACompactStripWhoseListNowFits_expandsIt', () => {
        // Arrange
        const strip = makeStrip();
        strip.element.classList.add('dungeon_strip--compact');
        setGroupsWidth(strip, 600, 600);

        // Act
        strip.updateCompact();

        // Assert
        expect(strip.element.classList.contains('dungeon_strip--compact')).toBe(false);
    });

    it('constructor_givenASelectedChip_makesItTheOnlyTabStopOfItsGroup', () => {
        // Act
        makeStrip("Zul'Gurub");

        // Assert
        expect(tabStops()).toEqual(['Blackrock Depths', "Zul'Gurub"]);
        expect(chip('Molten Core').tabIndex).toBe(-1);
    });

    it('constructor_givenNoSelectedChip_makesEachGroupsFirstChipItsTabStop', () => {
        // Act
        makeStrip();

        // Assert
        expect(tabStops()).toEqual(['Blackrock Depths', 'Molten Core']);
        expect(chip('Scarlet Monastery').tabIndex).toBe(-1);
    });

    it.each([
        ['ArrowRight', 'Blackrock Depths', "Temple of Atal'Hakkar"],
        ['ArrowRight', "Temple of Atal'Hakkar", 'Scarlet Monastery'],
        ['ArrowLeft', 'Scarlet Monastery', "Temple of Atal'Hakkar"],
        ['ArrowDown', 'Blackrock Depths', 'Scarlet Monastery'],
        ['ArrowDown', "Temple of Atal'Hakkar", 'Stratholme'],
        ['ArrowUp', 'Stratholme', "Temple of Atal'Hakkar"],
        ['Home', 'Stratholme', 'Blackrock Depths'],
        ['End', 'Blackrock Depths', 'Stratholme'],
        ['ArrowRight', 'Molten Core', "Zul'Gurub"],
    ])('keydown_given%sOn%s_focuses%s', (key, from, expected) => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip(from).focus();

        // Act
        const event = press(chip(from), key);

        // Assert
        expect(document.activeElement).toBe(chip(expected));
        expect(event.defaultPrevented).toBe(true);
    });

    it.each([
        ['ArrowRight', 'Stratholme'],
        ['ArrowLeft', 'Blackrock Depths'],
        ['ArrowDown', 'Scarlet Monastery'],
        ['ArrowUp', 'Blackrock Depths'],
        ['ArrowLeft', 'Molten Core'],
    ])('keydown_given%sAtTheEdgeOf%sGroup_staysOnIt', (key, from) => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip(from).focus();

        // Act
        const event = press(chip(from), key);

        // Assert
        expect(document.activeElement).toBe(chip(from));
        expect(event.defaultPrevented).toBe(true);
    });

    it('keydown_givenAnArrowKey_movesTheGroupsTabStopAlong', () => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        press(chip('Blackrock Depths'), 'ArrowRight');

        // Assert
        expect(tabStops()).toEqual(["Temple of Atal'Hakkar", 'Molten Core']);
    });

    it('keydown_givenAModifiedArrowKey_leavesItToTheBrowser', () => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        const event = new KeyboardEvent('keydown', {key: 'ArrowRight', altKey: true, bubbles: true, cancelable: true});
        chip('Blackrock Depths').dispatchEvent(event);

        // Assert
        expect(event.defaultPrevented).toBe(false);
        expect(document.activeElement).toBe(chip('Blackrock Depths'));
    });

    it('focus_givenAChipThatIsNotTheTabStop_makesItTheGroupsTabStop', () => {
        // Arrange
        makeStrip();

        // Act
        chip('Stratholme').focus();

        // Assert
        expect(tabStops()).toEqual(['Stratholme', 'Molten Core']);
    });

    it('typeahead_givenALetter_focusesTheNextChipWhoseAbbreviationStartsWithIt', () => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        press(chip('Blackrock Depths'), 's');

        // Assert
        expect(document.activeElement).toBe(chip("Temple of Atal'Hakkar"));
    });

    it('typeahead_givenTheSameLetterAgain_cyclesThroughTheChipsStartingWithIt', () => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        press(chip('Blackrock Depths'), 's');
        press(document.activeElement, 's');
        const second = document.activeElement;
        press(document.activeElement, 's');

        // Assert
        expect(second).toBe(chip('Scarlet Monastery'));
        expect(document.activeElement).toBe(chip('Stratholme'));
    });

    it('typeahead_givenSeveralLettersInQuickSuccession_matchesThemAsOneSearch', () => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        press(chip('Blackrock Depths'), 's');
        press(document.activeElement, 't');
        press(document.activeElement, 'r');

        // Assert
        expect(document.activeElement).toBe(chip('Stratholme'));
    });

    it('typeahead_givenAPauseBetweenLetters_startsANewSearch', () => {
        // Arrange
        vi.useFakeTimers();
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();
        press(chip('Blackrock Depths'), 's');

        // Act
        vi.advanceTimersByTime(DungeonStrip.TYPEAHEAD_RESET_MS + 1);
        press(document.activeElement, 'm');

        // Assert
        expect(document.activeElement).toBe(chip('Molten Core'));
    });

    it('typeahead_givenALetterOnlyANameStartsWith_matchesTheName', () => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        press(chip('Blackrock Depths'), 't');

        // Assert
        expect(document.activeElement).toBe(chip("Temple of Atal'Hakkar"));
    });

    it('typeahead_givenALetterOfAnotherGroupsChip_crossesIntoThatGroup', () => {
        // Arrange
        makeStrip();
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        press(chip('Blackrock Depths'), 'z');

        // Assert
        expect(document.activeElement).toBe(chip("Zul'Gurub"));
        expect(tabStops()).toEqual(['Blackrock Depths', "Zul'Gurub"]);
    });

    it('slash_givenAShownStrip_opensItWithTheFilterFocused', () => {
        // Arrange
        const strip = makeStrip();
        showStrip(strip);
        document.getElementById('outside').focus();

        // Act
        const event = press(document.getElementById('outside'), '/');

        // Assert
        expect(strip.isOpen()).toBe(true);
        expect(document.activeElement).toBe(strip.filterInput);
        expect(event.defaultPrevented).toBe(true);
    });

    it('slash_givenAChipFocused_opensTheFilterRatherThanSearchingTheChips', () => {
        // Arrange
        const strip = makeStrip();
        showStrip(strip);
        layOutGrid();
        chip('Blackrock Depths').focus();

        // Act
        press(chip('Blackrock Depths'), '/');

        // Assert
        expect(document.activeElement).toBe(strip.filterInput);
    });

    it('slash_givenFocusInATextField_typesItThere', () => {
        // Arrange
        const strip = makeStrip();
        showStrip(strip);
        const input = document.getElementById('outside_input');
        input.focus();

        // Act
        const event = press(input, '/');

        // Assert
        expect(strip.isOpen()).toBe(false);
        expect(document.activeElement).toBe(input);
        expect(event.defaultPrevented).toBe(false);
    });

    it('slash_givenAHiddenStrip_leavesTheKeyAlone', () => {
        // Arrange
        const strip = makeStrip();
        document.getElementById('outside').focus();

        // Act
        const event = press(document.getElementById('outside'), '/');

        // Assert
        expect(strip.isOpen()).toBe(false);
        expect(event.defaultPrevented).toBe(false);
    });

    it('slash_givenAnOpenModal_leavesTheKeyAlone', () => {
        // Arrange
        const strip = makeStrip();
        showStrip(strip);
        document.body.classList.add('modal-open');

        try {
            // Act
            press(document.getElementById('outside'), '/');

            // Assert
            expect(strip.isOpen()).toBe(false);
        } finally {
            document.body.classList.remove('modal-open');
        }
    });

    it('filter_givenAQuery_showsOnlyTheMatchingChipsAndTheirGroups', () => {
        // Arrange
        const strip = makeStrip();

        // Act
        strip.filterInput.value = 'strat';
        strip.filterInput.dispatchEvent(new Event('input'));

        // Assert
        expect(shownChips()).toEqual(['Stratholme']);
        expect(document.querySelector('.dungeon_strip_group--raid').hidden).toBe(true);
        expect(strip.filterEmpty.hidden).toBe(true);
    });

    it('filter_givenANameAndAnAbbreviationQuery_matchesLikeTheMobileSheet', () => {
        // Arrange
        const strip = makeStrip();

        // Act
        strip.filter('zul g');
        const byName = shownChips();
        strip.filter('BRD');

        // Assert
        expect(byName).toEqual(["Zul'Gurub"]);
        expect(shownChips()).toEqual(['Blackrock Depths']);
    });

    it('filter_givenNoMatch_showsTheEmptyMessage', () => {
        // Arrange
        const strip = makeStrip();

        // Act
        strip.filter('naxxramas');

        // Assert
        expect(shownChips()).toEqual([]);
        expect(strip.filterEmpty.hidden).toBe(false);
    });

    it('filter_givenATabStopItHides_movesTheTabStopToTheFirstShownChip', () => {
        // Arrange
        const strip = makeStrip();
        layOutGrid();

        // Act
        strip.filter('ar');

        // Assert
        expect(tabStops()).toEqual(["Temple of Atal'Hakkar", 'Molten Core']);
    });

    it('filterEnter_givenMatches_followsTheFirstShownChip', () => {
        // Arrange
        const strip = makeStrip();
        layOutGrid();
        strip.openFilter();
        strip.filter('ar');
        const followed = [];
        document.addEventListener('click', event => {
            followed.push(event.target.getAttribute('aria-label'));
            event.preventDefault();
        });

        // Act
        const event = press(strip.filterInput, 'Enter');

        // Assert
        expect(followed).toEqual(["Temple of Atal'Hakkar"]);
        expect(event.defaultPrevented).toBe(true);
    });

    it('filterArrowDown_givenMatches_focusesTheFirstShownChip', () => {
        // Arrange
        const strip = makeStrip();
        layOutGrid();
        strip.openFilter();
        strip.filter('ar');

        // Act
        press(strip.filterInput, 'ArrowDown');

        // Assert
        expect(document.activeElement).toBe(chip("Temple of Atal'Hakkar"));
    });

    it('arrowUp_givenTheFirstGroupsTopRowInTheOpenStrip_focusesTheFilter', () => {
        // Arrange
        const strip = makeStrip();
        layOutGrid();
        strip.setOpen(true);
        chip("Temple of Atal'Hakkar").focus();

        // Act
        press(chip("Temple of Atal'Hakkar"), 'ArrowUp');

        // Assert
        expect(document.activeElement).toBe(strip.filterInput);
    });

    it('filterEscape_givenAFilteredStrip_closesItClearsTheFilterAndReturnsFocus', () => {
        // Arrange
        const strip = makeStrip();
        showStrip(strip);
        const outside = document.getElementById('outside');
        outside.focus();
        press(outside, '/');
        strip.filterInput.value = 'strat';
        strip.filterInput.dispatchEvent(new Event('input'));

        // Act
        press(strip.filterInput, 'Escape');

        // Assert
        expect(strip.isOpen()).toBe(false);
        expect(strip.filterInput.value).toBe('');
        expect(shownChips()).toHaveLength(6);
        expect(document.activeElement).toBe(outside);
    });

    it('escape_givenAnOpenStripWithoutAnAllButton_keepsFocusOnTheChip', () => {
        // Arrange
        const strip = makeStrip();
        strip.allButton.style.display = 'none';
        strip.setOpen(true);
        chip('Molten Core').focus();

        // Act
        press(chip('Molten Core'), 'Escape');

        // Assert
        expect(strip.isOpen()).toBe(false);
        expect(document.activeElement).toBe(chip('Molten Core'));
    });

    it('searchButtonClick_givenAClosedStrip_opensItWithTheFilterFocused', () => {
        // Arrange
        const strip = makeStrip();
        strip.searchButton.focus();

        // Act
        strip.searchButton.click();

        // Assert
        expect(strip.isOpen()).toBe(true);
        expect(strip.searchButton.getAttribute('aria-expanded')).toBe('true');
        expect(document.activeElement).toBe(strip.filterInput);
    });

    it('searchButtonClick_givenAnOpenStrip_closesIt', () => {
        // Arrange
        const strip = makeStrip();
        strip.searchButton.click();
        const wasOpen = strip.isOpen();
        strip.searchButton.focus();

        // Act
        strip.searchButton.click();

        // Assert
        expect(wasOpen).toBe(true);
        expect(strip.isOpen()).toBe(false);
        expect(strip.searchButton.getAttribute('aria-expanded')).toBe('false');
        expect(document.activeElement).toBe(strip.searchButton);
    });

    it('filterEscape_givenTheFilterOpenedByTheSearchButton_returnsFocusToTheButton', () => {
        // Arrange
        const strip = makeStrip();
        strip.searchButton.focus();
        strip.searchButton.click();
        const focusedBeforeEscape = document.activeElement;

        // Act
        press(strip.filterInput, 'Escape');

        // Assert
        expect(focusedBeforeEscape).toBe(strip.filterInput);
        expect(strip.isOpen()).toBe(false);
        expect(document.activeElement).toBe(strip.searchButton);
    });

    it.each([
        ['AClosedStrip', false, false],
        ['AnOpenStrip', true, true],
    ])('searchButtonTooltip_given%s_isPreventedOnlyWhileOpen', (_, open, expected) => {
        // Arrange
        const strip = makeStrip();
        strip.setOpen(open);
        const event = new Event('show.bs.tooltip', {cancelable: true});

        // Act
        strip.searchButton.dispatchEvent(event);

        // Assert
        expect(event.defaultPrevented).toBe(expected);
    });

    it('pointerdown_givenAFilteredFlyoutAndAClickOutside_clearsTheFilter', () => {
        // Arrange
        const strip = makeStrip();
        strip.openFilter();
        strip.filterInput.value = 'strat';
        strip.filterInput.dispatchEvent(new Event('input'));

        // Act
        document.getElementById('outside').dispatchEvent(new Event('pointerdown', {bubbles: true}));

        // Assert
        expect(shownChips()).toHaveLength(6);
    });
});
