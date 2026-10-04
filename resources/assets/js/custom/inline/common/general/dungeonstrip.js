/**
 * The seasonless dungeon strip in the header: the readout names whichever chip is hovered or focused, and
 * the "All" button unfolds the strip wherever the list is too wide for it.
 */
class DungeonStrip {
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

        element.addEventListener('pointerover', this._onPointerOver.bind(this));
        element.addEventListener('pointerleave', this._onPointerLeave.bind(this));
        element.addEventListener('focusin', this._onFocusIn.bind(this));
        element.addEventListener('focusout', this._onFocusOut.bind(this));
        element.addEventListener('keydown', this._onKeyDown.bind(this));
        this.allButton.addEventListener('click', this._onAllClick.bind(this));
        document.addEventListener('pointerdown', this._onDocumentPointerDown.bind(this));
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

    _onKeyDown(event) {
        if (event.key === 'Escape' && this.isOpen()) {
            this.setOpen(false);
            this.allButton.focus();
        }
    }

    _onDocumentPointerDown(event) {
        if (this.isOpen() && !this.element.contains(event.target)) {
            this.setOpen(false);
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DungeonStrip};
}
