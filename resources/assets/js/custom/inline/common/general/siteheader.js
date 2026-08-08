/**
 * Decides if the header should be shrunk for the given scroll position.
 *
 * The shrink and unshrink thresholds are deliberately separated by more than the height the
 * header loses when it shrinks: shrinking shortens the document, which nudges the scroll
 * position, and a single threshold would let that nudge immediately re-cross it and flap
 * the header back and forth (#3118, #3851).
 *
 * @param {number} scrollY
 * @param {boolean} currentlyShrunk
 * @param {number} shrinkAt
 * @param {number} unshrinkAt
 * @returns {boolean}
 */
function shouldShrinkHeader(scrollY, currentlyShrunk, shrinkAt = 140, unshrinkAt = 40) {
    if (currentlyShrunk) {
        // Stay shrunk until scrolled back near the top
        return scrollY >= unshrinkAt;
    }

    return scrollY > shrinkAt;
}

class CommonGeneralSiteheader extends InlineCode {
    /**
     * Never shrink when the page barely scrolls - the height change itself would make up
     * most of the scrollable distance.
     */
    static MIN_SCROLLABLE_DISTANCE = 200;

    /**
     * How long scroll anchoring stays suppressed after the header starts growing back. Must
     * outlast the CSS height/padding transition in header.css (0.2s), since the document keeps
     * growing - and anchoring keeps re-evaluating - for its whole duration.
     */
    static SCROLL_ANCHOR_SUPPRESSION_MS = 400;

    activate() {
        super.activate();

        // #map_header wraps the whole floating map header (including the route bar) - prefer it
        // so the published height covers all bars, not just the included site header
        this.header = document.getElementById('map_header') ?? document.getElementById('site_header');
        if (this.header === null) {
            return;
        }

        // Publish the header's rendered height so dependents (e.g. the route sidebar) can
        // position themselves below it without hardcoded offsets.
        this._resizeObserver = new ResizeObserver(this._reportHeaderHeight.bind(this));
        this._resizeObserver.observe(this.header);
        this._reportHeaderHeight();

        // The map view renders the header permanently shrunk - no scroll handling there
        this.shrinkTarget = this.header.classList.contains('ksg-header') ?
            this.header : this.header.querySelector('.ksg-header');
        if (this.shrinkTarget === null || this.shrinkTarget.classList.contains('ksg-header--shrink-forced')) {
            return;
        }

        this._scrollTicking = false;
        window.addEventListener('scroll', () => {
            if (this._scrollTicking) {
                return;
            }
            this._scrollTicking = true;
            requestAnimationFrame(() => {
                this._scrollTicking = false;
                this._updateShrink();
            });
        }, {passive: true});

        this._updateShrink();
    }

    _reportHeaderHeight() {
        document.documentElement.style.setProperty(
            '--ksg-header-height',
            `${Math.ceil(this.header.getBoundingClientRect().height)}px`
        );
    }

    _updateShrink() {
        const scrollableDistance = document.documentElement.scrollHeight - window.innerHeight;
        const currentlyShrunk = this.shrinkTarget.classList.contains('ksg-header--shrink');

        const shrunk = scrollableDistance >= CommonGeneralSiteheader.MIN_SCROLLABLE_DISTANCE &&
            shouldShrinkHeader(window.scrollY, currentlyShrunk);

        if (shrunk !== currentlyShrunk) {
            if (shrunk) {
                // Shrinking wants the compensation - close any suppression window a recent
                // unshrink left open, or the shrink runs without it
                this._restoreScrollAnchoring();
            } else {
                this._suppressScrollAnchoring();
            }

            this.shrinkTarget.classList.toggle('ksg-header--shrink', shrunk);
        }
    }

    /**
     * Stop the browser from compensating for the header growing back (#3893).
     *
     * The bars stack in normal document flow since #3851, so unshrinking grows the document by
     * the height the header regains - above the viewport. Scroll anchoring then adds that same
     * height to the scroll position to keep the visible content stable, which eats the tail of
     * an upward fling (already decelerating by then) and leaves the page short of the top.
     *
     * Deliberately not applied when the header shrinks: there the compensation is wanted, as it
     * keeps the content the user is reading from jumping upwards mid-page.
     */
    _suppressScrollAnchoring() {
        document.documentElement.style.overflowAnchor = 'none';

        clearTimeout(this._scrollAnchorRestoreTimer);
        this._scrollAnchorRestoreTimer = setTimeout(
            this._restoreScrollAnchoring.bind(this),
            CommonGeneralSiteheader.SCROLL_ANCHOR_SUPPRESSION_MS
        );
    }

    /**
     * Hand scroll anchoring back to the browser. Suppression is always temporary - leaving it on
     * would stop the whole page from compensating for anything that resizes above the viewport,
     * such as an image loading in.
     */
    _restoreScrollAnchoring() {
        clearTimeout(this._scrollAnchorRestoreTimer);
        document.documentElement.style.removeProperty('overflow-anchor');
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonGeneralSiteheader, shouldShrinkHeader};
}
