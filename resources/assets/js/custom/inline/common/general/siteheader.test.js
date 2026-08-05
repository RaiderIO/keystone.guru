// ---------------------------------------------------------------------------
// CommonGeneralSiteheader is a global-script style class extending the bare
// global `InlineCode`. The hysteresis decision is a pure function
// (shouldShrinkHeader) so the flap-prevention logic is testable without DOM.
// ---------------------------------------------------------------------------

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {shouldShrinkHeader} = require('./siteheader');

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
