const {DungeonStrip} = require('./dungeonstrip');

describe('DungeonStrip', () => {
    /**
     * Three chips, one without view counts, and the selected dungeon in the readout - the markup of common/dungeon/list/chips, trimmed.
     *
     * @returns {DungeonStrip}
     */
    function makeStrip() {
        document.body.innerHTML = `
            <div class="dungeon_strip">
                <div class="dungeon_strip_readout" data-name="Blackrock Depths" data-image="http://test/brd.webp" data-views="Most viewed">
                    <img class="dungeon_strip_readout_image" src="http://test/brd.webp" alt=""/>
                    <span class="dungeon_strip_readout_text">
                        <span class="dungeon_strip_readout_name">Blackrock Depths</span>
                        <span class="dungeon_strip_readout_views">Most viewed</span>
                    </span>
                </div>
                <div class="dungeon_strip_groups" id="dungeon_strip_groups">
                    <a class="dungeon_strip_chip" href="/brd" aria-label="Blackrock Depths" data-image="http://test/brd.webp" data-views="Most viewed">BRD</a>
                    <a class="dungeon_strip_chip" href="/mc" aria-label="Molten Core" data-image="http://test/mc.webp" data-views="41% of top views">MC</a>
                    <a class="dungeon_strip_chip" href="/zg" aria-label="Zul'Gurub" data-image="http://test/zg.webp">ZG</a>
                </div>
                <button type="button" class="dungeon_strip_all" aria-expanded="false">All 2</button>
            </div>
            <a id="outside" href="/elsewhere">Elsewhere</a>`;

        return new DungeonStrip(document.querySelector('.dungeon_strip'));
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
});
