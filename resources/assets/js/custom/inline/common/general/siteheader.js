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

/**
 * The height the mobile navbar collapse may occupy before it must scroll internally.
 *
 * The header is sticky at the top of the viewport and the collapse expands in normal flow below
 * the brand row, so anything past the viewport bottom is unreachable - the page scroll moves the
 * document, not the pinned header (#4378).
 *
 * @param {number} viewportHeight The dynamic viewport height (window.innerHeight)
 * @param {number} collapseTop The collapse's distance from the top of the viewport
 * @param {number} bottomMargin Breathing room kept below the last menu item
 * @returns {number}
 */
function calculateNavbarCollapseMaxHeight(viewportHeight, collapseTop, bottomMargin = 8) {
    return Math.max(0, Math.floor(viewportHeight - collapseTop - bottomMargin));
}

/**
 * The tile shown in place of a dungeon image that failed to load: a hatched, unlit doorway in a neutral grey that
 * reads on every theme. A data URI, so the fallback itself can never fail.
 */
const HEADER_IMAGE_FALLBACK_SRC = 'data:image/svg+xml,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="90" viewBox="0 0 160 90">' +
    '<defs><pattern id="h" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">' +
    '<rect width="3" height="8" fill="#808080" fill-opacity="0.22"/></pattern></defs>' +
    '<rect width="160" height="90" fill="#808080" fill-opacity="0.14"/>' +
    '<rect width="160" height="90" fill="url(#h)"/>' +
    '<path d="M66 68V42a14 14 0 0 1 28 0v26M58 68h44" fill="none" stroke="#808080" stroke-opacity="0.8" ' +
    'stroke-width="3" stroke-linecap="round"/>' +
    '</svg>'
);

/**
 * Swaps every `img[data-image-fallback]` under the root that fails to load - now or later, when its src changes -
 * for the fallback tile, so a missing image never shows the browser's broken-image glyph or raw alt text.
 *
 * @param {HTMLElement} root
 */
function installHeaderImageFallback(root) {
    const useFallback = (image) => {
        if (image.matches('img[data-image-fallback]') && image.getAttribute('src') !== HEADER_IMAGE_FALLBACK_SRC) {
            image.src = HEADER_IMAGE_FALLBACK_SRC;
        }
    };

    // An image's error event does not bubble, but it does pass its ancestors in the capture phase
    root.addEventListener('error', (event) => {
        if (event.target instanceof HTMLImageElement) {
            useFallback(event.target);
        }
    }, true);

    // An image above the fold can fail ahead of the listener above being attached
    root.querySelectorAll('img[data-image-fallback]').forEach((image) => {
        if (image.complete && image.naturalWidth === 0 && image.getAttribute('src')) {
            useFallback(image);
        }
    });
}

/**
 * Moves focus to the skip link's target without navigating to its fragment: a fragment navigation adds a history
 * entry and fires popstate, which the map page reads as a floor change. Without JS the plain anchor still works.
 *
 * @param {HTMLAnchorElement} skipLink
 */
function installSkipLink(skipLink) {
    skipLink.addEventListener('click', (event) => {
        const target = document.getElementById(skipLink.hash.slice(1));
        if (target === null) {
            return;
        }

        event.preventDefault();
        target.focus();
    });
}

class CommonGeneralSiteheader extends InlineCode {
    /**
     * Never start shrinking when the page barely scrolls - the height change itself would make up
     * most of the scrollable distance. Only checked while the header is at full size: shrinking
     * shortens the document by the height it loses, so re-checking it while shrunk lets the
     * header's own shrink flip the guard and flap the header.
     */
    static MIN_SCROLLABLE_DISTANCE = 200;

    /**
     * How long scroll anchoring stays suppressed after the header starts growing back. Must
     * outlast the CSS height/padding transition in header.css (0.2s), since the document keeps
     * growing - and anchoring keeps re-evaluating - for its whole duration.
     */
    static SCROLL_ANCHOR_SUPPRESSION_MS = 400;

    /**
     * Breathing room kept between the last item of the open mobile menu and the viewport bottom.
     */
    static NAVBAR_COLLAPSE_BOTTOM_MARGIN = 8;

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
        this._initNavbarCollapse();
        this._initDungeonStrip();
        this._initDungeonSheet();
        const skipLink = document.querySelector('a.skip_link');
        if (skipLink !== null) {
            installSkipLink(skipLink);
        }
        installHeaderImageFallback(this.header);
        // The sheet is moved to <body>, out from under the header
        const dungeonSheet = document.getElementById('dungeon_sheet');
        if (dungeonSheet !== null) {
            installHeaderImageFallback(dungeonSheet);
        }

        this._resizeObserver = new ResizeObserver(this._onHeaderResized.bind(this));
        this._resizeObserver.observe(this.header);
        this._onHeaderResized();

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

    /**
     * The header's own height transition (shrink/unshrink) moves the open menu's top edge, and so
     * does anything else that reflows the bars - the observer catches every such change, which is
     * why neither the shrink toggle nor the collapse animation needs its own hook or timer.
     */
    _onHeaderResized() {
        this._reportHeaderHeight();
        this._updateNavbarCollapseMaxHeight();
        this.dungeonStrip?.updateLayout();
    }

    _initDungeonStrip() {
        const element = this.header.querySelector('.dungeon_strip');
        this.dungeonStrip = element === null ? null : new DungeonStrip(element);
    }

    _initDungeonSheet() {
        const element = document.getElementById('dungeon_sheet');
        this.dungeonSheet = element === null ? null : new DungeonSheet(element);
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

        const shrunk = (currentlyShrunk || scrollableDistance >= CommonGeneralSiteheader.MIN_SCROLLABLE_DISTANCE) &&
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
     * Publishes the mobile main menu's max height (#4378); header.css decides when the cap applies.
     */
    _initNavbarCollapse() {
        this.navbarCollapse = this.header.querySelector('.navbar-second .navbar-collapse');
        if (this.navbarCollapse === null) {
            return;
        }

        // Bootstrap fires `show` before it makes the element visible, so this only seeds the
        // estimate - the ResizeObserver refines it from the real geometry as the menu opens
        this.navbarCollapse.addEventListener('show.bs.collapse', this._updateNavbarCollapseMaxHeight.bind(this));

        // The header does not resize when only the viewport does (address bar, rotation)
        window.addEventListener('resize', this._updateNavbarCollapseMaxHeight.bind(this));
        window.addEventListener('orientationchange', this._updateNavbarCollapseMaxHeight.bind(this));
    }

    _updateNavbarCollapseMaxHeight() {
        if (!this.navbarCollapse) {
            return;
        }

        const maxHeight = calculateNavbarCollapseMaxHeight(
            window.innerHeight,
            this._navbarCollapseTop(),
            CommonGeneralSiteheader.NAVBAR_COLLAPSE_BOTTOM_MARGIN
        );

        document.documentElement.style.setProperty('--ksg-navbar-collapse-max-height', `${maxHeight}px`);
    }

    /**
     * Where the menu's top edge is (or will be while closed - falls back to the header's bottom edge).
     * @returns {number}
     */
    _navbarCollapseTop() {
        const rect = this.navbarCollapse.getBoundingClientRect();

        return rect.width === 0 && rect.height === 0 ?
            this.header.getBoundingClientRect().bottom : rect.top;
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
    module.exports = {CommonGeneralSiteheader, shouldShrinkHeader, calculateNavbarCollapseMaxHeight, installHeaderImageFallback, installSkipLink, HEADER_IMAGE_FALLBACK_SRC};
}
