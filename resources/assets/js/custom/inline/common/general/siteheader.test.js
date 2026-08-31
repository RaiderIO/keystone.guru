// ---------------------------------------------------------------------------
// CommonGeneralSiteheader is a global-script style class extending the bare
// global `InlineCode`. The hysteresis decision is a pure function
// (shouldShrinkHeader) so the flap-prevention logic is testable without DOM;
// the scroll-anchoring suppression needs one, but only a class on an element.
// ---------------------------------------------------------------------------

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonGeneralSiteheader, shouldShrinkHeader, calculateNavbarCollapseMaxHeight} = require('./siteheader');

describe('shouldShrinkHeader', () => {
    it('shouldShrinkHeader_givenTopOfPageNotShrunk_returnsFalse', () => {
        expect(shouldShrinkHeader(0, false)).toBe(false);
    });

    it('shouldShrinkHeader_givenScrollBelowShrinkThresholdNotShrunk_returnsFalse', () => {
        expect(shouldShrinkHeader(140, false)).toBe(false);
    });

    it('shouldShrinkHeader_givenScrollPastShrinkThresholdNotShrunk_returnsTrue', () => {
        expect(shouldShrinkHeader(141, false)).toBe(true);
    });

    it('shouldShrinkHeader_givenShrunkAndStillPastUnshrinkThreshold_returnsTrue', () => {
        // Between the two thresholds nothing changes - this is the hysteresis band
        expect(shouldShrinkHeader(100, true)).toBe(true);
        expect(shouldShrinkHeader(40, true)).toBe(true);
    });

    it('shouldShrinkHeader_givenShrunkAndScrolledBackToTop_returnsFalse', () => {
        expect(shouldShrinkHeader(39, true)).toBe(false);
        expect(shouldShrinkHeader(0, true)).toBe(false);
    });

    it('shouldShrinkHeader_givenShrinkInducedScrollNudge_neverUnshrinks', () => {
        // Arrange: user scrolls just past the shrink threshold; the header shrinks and the
        // document shortens by the header height delta (~35-40px), nudging scrollY down.
        const headerHeightDelta = 40;
        const scrollY = 141;

        // Act
        const shrunk = shouldShrinkHeader(scrollY, false);
        const afterNudge = shouldShrinkHeader(scrollY - headerHeightDelta, shrunk);

        // Assert: the nudged position stays inside the hysteresis band - no flap
        expect(shrunk).toBe(true);
        expect(afterNudge).toBe(true);
    });
});

describe('CommonGeneralSiteheader scroll anchoring', () => {
    /**
     * Build an instance wired straight to a shrink target, bypassing activate() - that path
     * needs a ResizeObserver and real layout, neither of which jsdom provides, and neither of
     * which the anchoring decision depends on.
     *
     * @param {boolean} currentlyShrunk
     * @returns {CommonGeneralSiteheader}
     */
    function makeSiteheader(currentlyShrunk) {
        document.body.innerHTML = `<div id="site_header" class="ksg-header${currentlyShrunk ? ' ksg-header--shrink' : ''}"></div>`;

        const siteheader = new CommonGeneralSiteheader('siteheader', 'common/general/siteheader', {});
        siteheader.shrinkTarget = document.getElementById('site_header');

        return siteheader;
    }

    /**
     * @param {number} scrollY
     */
    function scrollTo(scrollY) {
        window.scrollY = scrollY;
        // Well past MIN_SCROLLABLE_DISTANCE, so the page always counts as scrollable
        vi.spyOn(document.documentElement, 'scrollHeight', 'get').mockReturnValue(5000);
        window.innerHeight = 1000;
    }

    beforeEach(() => {
        vi.useFakeTimers();
        document.documentElement.style.removeProperty('overflow-anchor');
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('updateShrink_givenHeaderUnshrinks_suppressesScrollAnchoringForTheTransition', () => {
        // Arrange: shrunk header, scrolled back to the top
        const siteheader = makeSiteheader(true);
        scrollTo(0);

        // Act
        siteheader._updateShrink();

        // Assert: the class flipped and anchoring cannot move the scroll position meanwhile
        expect(siteheader.shrinkTarget.classList.contains('ksg-header--shrink')).toBe(false);
        expect(document.documentElement.style.overflowAnchor).toBe('none');
    });

    it('updateShrink_givenSuppressionWindowElapsed_restoresScrollAnchoring', () => {
        // Arrange
        const siteheader = makeSiteheader(true);
        scrollTo(0);
        siteheader._updateShrink();

        // Act
        vi.advanceTimersByTime(CommonGeneralSiteheader.SCROLL_ANCHOR_SUPPRESSION_MS);

        // Assert: suppression is temporary - leaving it on would break anchoring site-wide
        expect(document.documentElement.style.overflowAnchor).toBe('');
    });

    it('updateShrink_givenSuppressionWindowStillOpen_keepsScrollAnchoringSuppressed', () => {
        // Arrange
        const siteheader = makeSiteheader(true);
        scrollTo(0);
        siteheader._updateShrink();

        // Act: the CSS transition (0.2s) must be fully covered
        vi.advanceTimersByTime(CommonGeneralSiteheader.SCROLL_ANCHOR_SUPPRESSION_MS - 1);

        // Assert
        expect(document.documentElement.style.overflowAnchor).toBe('none');
    });

    it('updateShrink_givenHeaderShrinks_leavesScrollAnchoringAlone', () => {
        // Arrange: unshrunk header, scrolled past the shrink threshold
        const siteheader = makeSiteheader(false);
        scrollTo(500);

        // Act
        siteheader._updateShrink();

        // Assert: shrinking wants the compensation - it keeps mid-page content from jumping
        expect(siteheader.shrinkTarget.classList.contains('ksg-header--shrink')).toBe(true);
        expect(document.documentElement.style.overflowAnchor).toBe('');
    });

    it('updateShrink_givenShrinkWithinTheSuppressionWindow_restoresScrollAnchoringImmediately', () => {
        // Arrange: unshrink at the top, then scroll straight back down past the shrink
        // threshold before the suppression window has closed
        const siteheader = makeSiteheader(true);
        scrollTo(0);
        siteheader._updateShrink();

        // Act
        scrollTo(500);
        siteheader._updateShrink();

        // Assert: the shrink must not inherit the open window - it needs the compensation
        expect(siteheader.shrinkTarget.classList.contains('ksg-header--shrink')).toBe(true);
        expect(document.documentElement.style.overflowAnchor).toBe('');
    });

    it('updateShrink_givenNoStateChange_leavesScrollAnchoringAlone', () => {
        // Arrange: shrunk header inside the hysteresis band
        const siteheader = makeSiteheader(true);
        scrollTo(100);

        // Act
        siteheader._updateShrink();

        // Assert
        expect(siteheader.shrinkTarget.classList.contains('ksg-header--shrink')).toBe(true);
        expect(document.documentElement.style.overflowAnchor).toBe('');
    });

    it('updateShrink_givenAShrinkUnshrinkCycleWithinTheWindow_givesTheSecondUnshrinkAFullWindow', () => {
        // Arrange: unshrink, then shrink and unshrink again before the first timer would fire.
        // (An unshrink can only follow another unshrink through a shrink, which restores - so
        // this cycle is the only way a second suppression window is ever opened.)
        const siteheader = makeSiteheader(true);
        scrollTo(0);
        siteheader._updateShrink();

        vi.advanceTimersByTime(CommonGeneralSiteheader.SCROLL_ANCHOR_SUPPRESSION_MS - 10);
        scrollTo(500);
        siteheader._updateShrink();
        scrollTo(0);
        siteheader._updateShrink();

        // Act: past the point the *first* timer would have fired
        vi.advanceTimersByTime(20);

        // Assert: a stale timer must not cut the second suppression window short
        expect(document.documentElement.style.overflowAnchor).toBe('none');
    });
});

describe('calculateNavbarCollapseMaxHeight', () => {
    it('calculateNavbarCollapseMaxHeight_givenRoomBelowTheBrandRow_returnsTheRemainingViewport', () => {
        // A 640px tall phone viewport with a 56px brand row above the collapse
        expect(calculateNavbarCollapseMaxHeight(640, 56, 8)).toBe(576);
    });

    it('calculateNavbarCollapseMaxHeight_givenFractionalLayout_roundsDownToStayInsideTheViewport', () => {
        // Rounding up would push the last item back below the fold - the whole bug (#4378)
        expect(calculateNavbarCollapseMaxHeight(640, 55.6, 8)).toBe(576);
    });

    it('calculateNavbarCollapseMaxHeight_givenNoRoomLeft_clampsAtZero', () => {
        // A viewport shorter than the header itself must not produce a negative max-height
        expect(calculateNavbarCollapseMaxHeight(50, 56, 8)).toBe(0);
    });
});

describe('CommonGeneralSiteheader mobile menu height', () => {
    /**
     * @param {number} collapseTop
     * @returns {CommonGeneralSiteheader}
     */
    function makeSiteheaderWithCollapse(collapseTop = 56) {
        document.body.innerHTML = `
            <div id="site_header" class="ksg-header">
                <nav class="navbar navbar-second">
                    <div class="collapse navbar-collapse" id="mainNavbar"></div>
                </nav>
            </div>`;

        const siteheader = new CommonGeneralSiteheader('siteheader', 'common/general/siteheader', {});
        siteheader.header = document.getElementById('site_header');
        siteheader.shrinkTarget = siteheader.header;
        siteheader._initNavbarCollapse();

        // jsdom lays nothing out, so the collapse's position is stubbed the way the shrink
        // tests stub scrollHeight/innerHeight
        vi.spyOn(siteheader.navbarCollapse, 'getBoundingClientRect')
            .mockReturnValue({top: collapseTop});
        window.innerHeight = 640;

        return siteheader;
    }

    afterEach(() => {
        document.documentElement.style.removeProperty('--ksg-navbar-collapse-max-height');
    });

    it('initNavbarCollapse_givenMenuOpens_capsItToTheVisibleViewport', () => {
        // Arrange
        const siteheader = makeSiteheaderWithCollapse();

        // Act
        siteheader.navbarCollapse.dispatchEvent(new Event('show.bs.collapse'));

        // Assert: 640 viewport - 56 brand row - 8 margin
        expect(document.documentElement.style.getPropertyValue('--ksg-navbar-collapse-max-height'))
            .toBe('576px');
    });

    it('initNavbarCollapse_givenViewportResizedWhileOpen_recalculatesTheCap', () => {
        // Arrange: menu open on a tall viewport, then the browser chrome expands
        const siteheader = makeSiteheaderWithCollapse();
        siteheader.navbarCollapse.dispatchEvent(new Event('show.bs.collapse'));
        window.innerHeight = 400;

        // Act
        window.dispatchEvent(new Event('resize'));

        // Assert
        expect(document.documentElement.style.getPropertyValue('--ksg-navbar-collapse-max-height'))
            .toBe('336px');
    });

    it('initNavbarCollapse_givenMenuClosed_dropsTheCapAgain', () => {
        // Arrange
        const siteheader = makeSiteheaderWithCollapse();
        siteheader.navbarCollapse.dispatchEvent(new Event('show.bs.collapse'));

        // Act
        siteheader.navbarCollapse.dispatchEvent(new Event('hidden.bs.collapse'));

        // Assert: a stale cap would shrink the menu the next time it opens at a different offset
        expect(document.documentElement.style.getPropertyValue('--ksg-navbar-collapse-max-height'))
            .toBe('');
    });

    it('updateShrink_givenHeaderUnshrinksWithTheMenuOpen_recalculatesTheCap', () => {
        // Arrange: menu opened while the header was shrunk, then scrolled back to the top -
        // the brand row regains its padding and pushes the collapse down
        const siteheader = makeSiteheaderWithCollapse(48);
        siteheader.shrinkTarget.classList.add('ksg-header--shrink');
        siteheader.navbarCollapse.dispatchEvent(new Event('show.bs.collapse'));
        expect(document.documentElement.style.getPropertyValue('--ksg-navbar-collapse-max-height'))
            .toBe('584px');

        siteheader.navbarCollapse.getBoundingClientRect.mockReturnValue({top: 64});
        window.scrollY = 0;
        vi.spyOn(document.documentElement, 'scrollHeight', 'get').mockReturnValue(5000);

        // Act
        siteheader._updateShrink();

        // Assert: keeping the shrunk measurement would leave the last items below the fold
        expect(siteheader.shrinkTarget.classList.contains('ksg-header--shrink')).toBe(false);
        expect(document.documentElement.style.getPropertyValue('--ksg-navbar-collapse-max-height'))
            .toBe('568px');
    });

    it('initNavbarCollapse_givenNoCollapseInTheHeader_doesNothing', () => {
        // Arrange: the map header renders without a main navbar collapse
        document.body.innerHTML = '<div id="site_header" class="ksg-header"></div>';
        const siteheader = new CommonGeneralSiteheader('siteheader', 'common/general/siteheader', {});
        siteheader.header = document.getElementById('site_header');

        // Act
        siteheader._initNavbarCollapse();
        siteheader._updateNavbarCollapseMaxHeight();

        // Assert
        expect(siteheader.navbarCollapse).toBeNull();
        expect(document.documentElement.style.getPropertyValue('--ksg-navbar-collapse-max-height'))
            .toBe('');
    });
});
