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
        this.allButton.addEventListener('click', () => this.setOpen(!this.isOpen()));
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
        this.readoutViews.textContent = source.views ?? '';
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
