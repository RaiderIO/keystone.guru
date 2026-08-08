// ---------------------------------------------------------------------------
// CommonGeneralSiteheader is a global-script style class extending the bare
// global `InlineCode`. The hysteresis decision is a pure function
// (shouldShrinkHeader) so the flap-prevention logic is testable without DOM;
// the scroll-anchoring suppression needs one, but only a class on an element.
// ---------------------------------------------------------------------------

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonGeneralSiteheader, shouldShrinkHeader} = require('./siteheader');

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
