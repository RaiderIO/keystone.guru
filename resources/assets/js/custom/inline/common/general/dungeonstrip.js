/**
 * The seasonless dungeon strip in the header: the readout names whichever chip is hovered or focused, and
 * the "All" button unfolds the strip wherever the list is too wide for it.
 *
 * Each chip group is a single tab stop: the arrow keys move between its chips the way they are laid out, Home and
 * End jump to its ends, and typing a letter jumps to the next chip whose abbreviation or name starts with it. The
 * search button, or "/" anywhere on the page, unfolds the strip with its filter focused.
 */
class DungeonStrip {
    /**
     * How long typed letters keep adding up to one search before the next letter starts a new one.
     */
    static TYPEAHEAD_RESET_MS = 500;

    /**
     * @param {HTMLElement} element The .dungeon_strip
     */
    constructor(element) {
        this.element = element;
        this.readout = element.querySelector('.dungeon_strip_readout');
        this.readoutImage = element.querySelector('.dungeon_strip_readout_image');
        this.readoutName = element.querySelector('.dungeon_strip_readout_name');
        this.readoutViews = element.querySelector('.dungeon_strip_readout_views');
        this.groups = element.querySelector('.dungeon_strip_groups');
        this.allButton = element.querySelector('.dungeon_strip_all');
        this.searchButton = element.querySelector('.dungeon_strip_search');
        this.filterInput = element.querySelector('.dungeon_strip_filter_input');
        this.filterEmpty = element.querySelector('.dungeon_strip_filter_empty');
        this.chips = [...element.querySelectorAll('.dungeon_strip_chip')];
        this.typeahead = '';
        this.typeaheadTimer = null;
        this.focusBeforeFilter = null;

        this._initTabStops();

        element.addEventListener('pointerover', this._onPointerOver.bind(this));
        element.addEventListener('pointerleave', this._onPointerLeave.bind(this));
        element.addEventListener('focusin', this._onFocusIn.bind(this));
        element.addEventListener('focusout', this._onFocusOut.bind(this));
        element.addEventListener('keydown', this._onKeyDown.bind(this));
        this.allButton.addEventListener('click', this._onAllClick.bind(this));
        this.searchButton?.addEventListener('click', this._onSearchClick.bind(this));
        // Its tooltip would cover the flyout it opened; a hover's tooltip is only shown after the click
        this.searchButton?.addEventListener('show.bs.tooltip', event => {
            if (this.isOpen()) {
                event.preventDefault();
            }
        });
        this.filterInput?.addEventListener('input', () => this.filter(this.filterInput.value));
        this.filterInput?.addEventListener('keydown', this._onFilterKeyDown.bind(this));
        document.addEventListener('pointerdown', this._onDocumentPointerDown.bind(this));
        document.addEventListener('keydown', this._onDocumentKeyDown.bind(this));
    }

    /**
     * @returns {boolean}
     */
    isOpen() {
        return this.element.classList.contains('is-open');
    }

    /**
     * @param {boolean} open
     */
    setOpen(open) {
        this.element.classList.toggle('is-open', open);
        this.allButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        this.searchButton?.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (!open && this.filterInput !== null && this.filterInput.value !== '') {
            this.filterInput.value = '';
            this.filter('');
        }
    }

    /**
     * Unfolds the strip with the filter focused, remembering where focus came from so Escape can return it there.
     *
     * @param {Element|null} returnFocusTo Where Escape puts focus back
     */
    openFilter(returnFocusTo = document.activeElement) {
        if (this.filterInput === null) {
            return;
        }

        this.focusBeforeFilter = returnFocusTo;
        this.setOpen(true);
        this.filterInput.focus();
    }

    /**
     * Hides the chips that do not match, and the groups that leaves empty, with the mobile sheet's matcher.
     *
     * @param {string} query
     */
    filter(query) {
        for (const chip of this.chips) {
            chip.hidden = !DungeonSheet.rowMatches(DungeonStrip._filterTextFor(chip), query);
        }

        for (const group of this._chipGroups()) {
            group.hidden = group.querySelector('.dungeon_strip_chip:not([hidden])') === null;
        }

        if (this.filterEmpty !== null) {
            this.filterEmpty.hidden = this._visibleChips().length > 0;
        }

        this._syncTabStops();
    }

    /**
     * Names the given chip's dungeon and its views in the readout, or the selected dungeon's when no chip is given.
     *
     * @param {HTMLElement|null} chip
     */
    showInReadout(chip) {
        const source = chip === null ? this.readout.dataset : chip.dataset;

        this.readoutName.textContent = chip === null ? source.name : chip.getAttribute('aria-label');
        this.readoutImage.src = source.image;
        this.readoutViews.textContent = source.viewShare === undefined ? '' : DungeonStrip.describeViewShare(parseFloat(source.viewShare));
        this.fitReadoutName();
    }

    /**
     * A name too long for the readout's two lines gets a third at a smaller size, rather than losing its end - which
     * is where a dungeon's wings differ ("Scarlet Monastery - Armory").
     */
    fitReadoutName() {
        this.readoutName.classList.remove('dungeon_strip_readout_name--long');
        this.readoutName.classList.toggle('dungeon_strip_readout_name--long', this.readoutName.scrollHeight > this.readoutName.clientHeight);
    }

    /**
     * Everything that depends on the strip's measured size, which changes with the header's.
     */
    updateLayout() {
        this.fitReadoutName();
        this.updateCompact();
    }

    /**
     * Words a dungeon's views as a share of the most viewed dungeon's, the way the chips' titles do.
     *
     * @param {number} viewShare Between 0 and 1
     * @returns {string}
     */
    static describeViewShare(viewShare) {
        if (viewShare >= 1) {
            return lang.get('js.dungeon_strip_most_viewed');
        }

        if (viewShare <= 0) {
            return lang.get('js.dungeon_strip_not_viewed');
        }

        // Never "0%" for a dungeon that was viewed, nor "100%" for one that is not the most viewed
        return lang.get('js.dungeon_strip_view_share', {percent: Math.min(99, Math.max(1, Math.round(viewShare * 100)))});
    }

    /**
     * A list wider than the strip clips at its right edge and offers the "All" flyout instead. Measured with
     * the group labels shown and without the "All" button, so the result does not depend on the last one.
     */
    updateCompact() {
        if (this.isOpen()) {
            return;
        }

        this.element.classList.remove('dungeon_strip--compact');
        this.element.classList.toggle('dungeon_strip--compact', this.groups.scrollWidth > this.groups.clientWidth);
    }

    /**
     * @param {EventTarget|null} target
     * @returns {HTMLElement|null}
     */
    _chipFor(target) {
        return target instanceof Element ? target.closest('.dungeon_strip_chip') : null;
    }

    _onPointerOver(event) {
        const chip = this._chipFor(event.target);
        if (chip !== null) {
            this.showInReadout(chip);
        }
    }

    _onPointerLeave() {
        this.showInReadout(this._chipFor(document.activeElement));
    }

    _onFocusIn(event) {
        const chip = this._chipFor(event.target);
        if (chip === null) {
            return;
        }

        this._setTabStop(chip);
        this.showInReadout(chip);

        // Tabbing onto a chip the single row clips away unfolds the strip, so focus never lands out of sight
        if (!this.isOpen() && chip.getBoundingClientRect().right > this.groups.getBoundingClientRect().right) {
            this.setOpen(true);
        }
    }

    _onFocusOut(event) {
        if (event.relatedTarget instanceof Node && this.element.contains(event.relatedTarget)) {
            return;
        }

        this.showInReadout(null);
        this.setOpen(false);
    }

    /**
     * The flyout's chips come before the button in the tab order, so a keyboard user who unfolds it is moved into it.
     *
     * @param {MouseEvent} event
     */
    _onAllClick(event) {
        this.setOpen(!this.isOpen());

        // A click from Enter or Space has no pointer behind it
        if (this.isOpen() && event.detail === 0) {
            (this.groups.querySelector('.dungeon_strip_chip[aria-current]') ?? this.groups.querySelector('.dungeon_strip_chip'))?.focus();
        }
    }

    /**
     * Toggles the flyout like the "All" button does, but opens it with the filter focused.
     */
    _onSearchClick() {
        if (typeof bootstrap !== 'undefined') {
            bootstrap.Tooltip.getInstance(this.searchButton)?.hide();
        }

        if (this.isOpen()) {
            this.setOpen(false);
        } else {
            this.openFilter(this.searchButton);
        }
    }

    /**
     * @param {KeyboardEvent} event
     */
    _onKeyDown(event) {
        if (event.key === 'Escape' && this.isOpen()) {
            event.preventDefault();
            this._close(event.target === this.filterInput ? this.focusBeforeFilter : event.target);

            return;
        }

        const chip = this._chipFor(event.target);
        if (chip !== null) {
            this._onChipKeyDown(event, chip);
        }
    }

    /**
     * Closes the flyout and puts focus where it can stay: back where "/" was pressed or on the search button that
     * opened it, else on the "All" button that reopens the flyout, else on the chip itself when every chip fits the
     * strip and there is no "All" button.
     *
     * @param {EventTarget|null} focusFrom
     */
    _close(focusFrom) {
        const isElement = focusFrom instanceof HTMLElement && focusFrom.isConnected && focusFrom !== document.body;
        let target = null;
        if (isElement && (!this.element.contains(focusFrom) || focusFrom === this.searchButton)) {
            target = focusFrom;
        } else if (getComputedStyle(this.allButton).display !== 'none') {
            target = this.allButton;
        } else if (isElement && this._chipFor(focusFrom) !== null) {
            target = focusFrom;
        }

        this.setOpen(false);

        if (target === null) {
            document.activeElement?.blur();
        } else {
            target.focus();
        }
    }

    /**
     * @param {KeyboardEvent} event
     * @param {HTMLElement} chip
     */
    _onChipKeyDown(event, chip) {
        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        let target;
        switch (event.key) {
            case 'ArrowLeft':
            case 'ArrowRight': {
                const chips = this._rowsOf(this._groupOf(chip)).flat();
                target = chips[chips.indexOf(chip) + (event.key === 'ArrowRight' ? 1 : -1)];
                break;
            }
            case 'ArrowUp':
            case 'ArrowDown':
                target = this._verticalNeighbour(chip, event.key === 'ArrowDown' ? 1 : -1);
                break;
            case 'Home':
                target = this._rowsOf(this._groupOf(chip)).flat()[0];
                break;
            case 'End':
                target = this._rowsOf(this._groupOf(chip)).flat().at(-1);
                break;
            default:
                // "/" is left to the document, which opens the filter
                if (event.key.length !== 1 || event.key === '/' || event.key.trim() === '') {
                    return;
                }
                target = this._typeaheadTarget(chip, event.key);
        }

        event.preventDefault();
        target?.focus();
    }

    /**
     * The chip in the row above or below with its centre nearest the given chip's. Up from the first group's top row
     * of the open flyout reaches the filter.
     *
     * @param {HTMLElement} chip
     * @param {number} direction -1 for up, 1 for down
     * @returns {HTMLElement|null}
     */
    _verticalNeighbour(chip, direction) {
        const group = this._groupOf(chip);
        const rows = this._rowsOf(group);
        const rowIndex = rows.findIndex(row => row.includes(chip));
        const row = rows[rowIndex + direction];

        if (row === undefined) {
            const isFirstGroup = this._chipGroups().find(other => !other.hidden) === group;

            return direction < 0 && rowIndex === 0 && isFirstGroup && this.isOpen() ? this.filterInput : null;
        }

        const centre = DungeonStrip._centreX(chip);

        return row.reduce((nearest, other) =>
            Math.abs(DungeonStrip._centreX(other) - centre) < Math.abs(DungeonStrip._centreX(nearest) - centre) ? other : nearest
        );
    }

    /**
     * The next chip, from any group, whose abbreviation or name starts with what was typed. Typing one letter
     * repeatedly cycles through the chips starting with it.
     *
     * @param {HTMLElement} chip
     * @param {string} key
     * @returns {HTMLElement|null}
     */
    _typeaheadTarget(chip, key) {
        clearTimeout(this.typeaheadTimer);
        this.typeaheadTimer = setTimeout(() => {
            this.typeahead = '';
        }, DungeonStrip.TYPEAHEAD_RESET_MS);

        this.typeahead += DungeonSheet.foldForFilter(key);
        const isRepeat = [...this.typeahead].every(letter => letter === this.typeahead[0]);
        const term = isRepeat ? this.typeahead[0] : this.typeahead;

        const chips = this._chipGroups().flatMap(group => this._rowsOf(group).flat());
        // A new or repeated letter moves on from the current chip; a longer search may still match it
        const start = chips.indexOf(chip) + (isRepeat ? 1 : 0);

        for (let i = 0; i < chips.length; i++) {
            const candidate = chips[(start + i) % chips.length];
            const texts = [candidate.textContent.trim(), candidate.getAttribute('aria-label') ?? ''];
            if (texts.some(text => DungeonSheet.foldForFilter(text).startsWith(term))) {
                return candidate;
            }
        }

        return null;
    }

    /**
     * @param {KeyboardEvent} event
     */
    _onFilterKeyDown(event) {
        if (event.isComposing) {
            return;
        }

        const first = this._chipGroups().flatMap(group => this._rowsOf(group).flat())[0];
        if (event.key === 'Enter') {
            event.preventDefault();
            first?.click();
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            first?.focus();
        }
    }

    /**
     * @param {KeyboardEvent} event
     */
    _onDocumentKeyDown(event) {
        if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey || event.defaultPrevented) {
            return;
        }

        // Hidden below the lg breakpoint, where the mobile sheet has a filter of its own; and a modal keeps focus
        if (DungeonStrip._isTypingTarget(event.target) || !this.element.isConnected || this.element.getClientRects().length === 0 ||
            document.body.classList.contains('modal-open')) {
            return;
        }

        event.preventDefault();
        this.openFilter();
    }

    _onDocumentPointerDown(event) {
        if (this.isOpen() && !this.element.contains(event.target)) {
            this.setOpen(false);
        }
    }

    /**
     * Every group starts with its tab stop on the selected dungeon's chip, or else on its first chip.
     */
    _initTabStops() {
        for (const group of this._chipGroups()) {
            const chips = this._chipsOf(group);
            this._setTabStop(chips.find(chip => chip.hasAttribute('aria-current')) ?? this._rowsOf(group).flat()[0]);
        }
    }

    /**
     * Moves a group's tab stop off a chip the filter hid, onto the first chip still shown.
     */
    _syncTabStops() {
        for (const group of this._chipGroups()) {
            const stop = this._chipsOf(group).find(chip => chip.tabIndex === 0);
            if (stop === undefined || stop.hidden) {
                this._setTabStop(this._rowsOf(group).flat()[0]);
            }
        }
    }

    /**
     * @param {HTMLElement|undefined} chip
     */
    _setTabStop(chip) {
        if (chip === undefined) {
            return;
        }

        for (const other of this._chipsOf(this._groupOf(chip))) {
            other.tabIndex = other === chip ? 0 : -1;
        }
    }

    /**
     * @returns {HTMLElement[]}
     */
    _chipGroups() {
        const groups = [...this.groups.querySelectorAll('.dungeon_strip_group')];

        return groups.length > 0 ? groups : [this.groups];
    }

    /**
     * @param {HTMLElement} chip
     * @returns {HTMLElement}
     */
    _groupOf(chip) {
        return chip.closest('.dungeon_strip_group') ?? this.groups;
    }

    /**
     * @param {HTMLElement} group
     * @returns {HTMLElement[]}
     */
    _chipsOf(group) {
        return [...group.querySelectorAll('.dungeon_strip_chip')];
    }

    /**
     * @returns {HTMLElement[]}
     */
    _visibleChips() {
        return this.chips.filter(chip => !chip.hidden);
    }

    /**
     * A group's shown chips as they are laid out - top to bottom, each row left to right - whatever their order in
     * the markup. The closed strip flows them down two-row columns, the open flyout wraps them in rows.
     *
     * @param {HTMLElement} group
     * @returns {HTMLElement[][]}
     */
    _rowsOf(group) {
        if (group.hidden) {
            return [];
        }

        const entries = this._chipsOf(group)
            .filter(chip => !chip.hidden)
            .map(chip => ({chip, rect: chip.getBoundingClientRect()}))
            .sort((a, b) => a.rect.top - b.rect.top);

        const rows = [];
        for (const entry of entries) {
            const row = rows.at(-1);
            if (row !== undefined && entry.rect.top - row[0].rect.top <= row[0].rect.height / 2) {
                row.push(entry);
            } else {
                rows.push([entry]);
            }
        }

        return rows.map(row => row.sort((a, b) => a.rect.left - b.rect.left).map(entry => entry.chip));
    }

    /**
     * @param {HTMLElement} chip
     * @returns {number}
     */
    static _centreX(chip) {
        const rect = chip.getBoundingClientRect();

        return rect.left + rect.width / 2;
    }

    /**
     * The same name-and-abbreviation text the mobile sheet's rows filter on.
     *
     * @param {HTMLElement} chip
     * @returns {string}
     */
    static _filterTextFor(chip) {
        return `${chip.getAttribute('aria-label') ?? ''} ${chip.textContent.trim()}`.toLowerCase();
    }

    /**
     * @param {EventTarget|null} target
     * @returns {boolean}
     */
    static _isTypingTarget(target) {
        return target instanceof HTMLElement &&
            (target.isContentEditable || target.closest('input, textarea, select') !== null);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DungeonStrip};
}
