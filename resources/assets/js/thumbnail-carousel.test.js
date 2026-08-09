// ---------------------------------------------------------------------------
// Covers $.fn.thumbnailCarousel (#3595), the self-owned replacement for the removed
// `lightslider` plugin. It turns the <ul> of route thumbnails on a dungeon route card
// into a 1-up carousel with prev/next arrows, pointer dragging and lazy-loaded
// off-screen images.
//
// jsdom has no layout (offsetWidth is always 0), so these assert the widget's state
// machine - index, transform, ARIA, image loading - rather than anything pixel-based.
//
// Multi-slide carousels permanently carry two decorative clones (one of the last slide
// prepended, one of the first slide appended), so a wrap has somewhere to animate onto without
// mutating the DOM - see the "slot numbering" comment in thumbnail-carousel.js. That shifts
// every real slide `i` to DOM child `i + 1` and to visual slot `i + 1` (transform
// `translateX(-(i + 1) * 100%)`), which is why `realSlide()` below exists instead of indexing
// `li`/`img` NodeLists directly.
// ---------------------------------------------------------------------------

const $ = require('jquery');
require('./thumbnail-carousel');

/**
 * Renders the markup the blades emit: the first slide eager, the rest lazy behind data-src.
 *
 * @param {number} slideCount
 * @returns {HTMLElement} The <ul> track the widget is initialised on.
 */
function createCarousel(slideCount) {
    let slidesHtml = '';
    for (let i = 0; i < slideCount; i++) {
        const src = i === 0 ? `src="${i}.jpg"` : `src="//:0" data-src="${i}.jpg"`;
        slidesHtml += `<li class="thumbnail-carousel__slide"><img class="thumbnail" ${src}/></li>`;
    }
    document.body.innerHTML = `<div class="thumbnail-carousel">
        <ul class="thumbnail-carousel__track">${slidesHtml}</ul>
    </div>`;

    return document.querySelector('.thumbnail-carousel__track');
}

/**
 * The real slide `<li>` at the given index - i.e. skipping the decorative start clone every
 * multi-slide carousel's track begins with.
 *
 * @param {HTMLElement} track
 * @param {number} index
 * @returns {Element}
 */
function realSlide(track, index) {
    return track.children[index + 1];
}

/**
 * @param {HTMLElement} track
 * @param {number} index
 * @returns {?string}
 */
function realSlideSrc(track, index) {
    return realSlide(track, index).querySelector('img').getAttribute('src');
}

/**
 * @param {HTMLElement} track
 * @param {number} index
 * @returns {?string}
 */
function slideSrc(track, index) {
    return track.querySelectorAll('img')[index].getAttribute('src');
}

/**
 * Simulates a horizontal drag across the track. jsdom doesn't implement PointerEvent, so the
 * events go through jQuery's own event object with the properties the widget reads.
 *
 * @param {HTMLElement} track
 * @param {number} distance Positive drags right (towards the previous slide).
 */
function drag(track, distance, endEvent = 'pointerup') {
    const $track = $(track);
    $track.trigger($.Event('pointerdown', {button: 0, pointerId: 1, clientX: 200}));
    $track.trigger($.Event('pointermove', {pointerId: 1, clientX: 200 + distance}));
    $track.trigger($.Event(endEvent, {pointerId: 1, clientX: 200 + distance}));
}

/**
 * Simulates the browser finishing the CSS transition a wrap animates onto a decorative clone
 * slot - jsdom never runs real transitions, so nothing fires this on its own.
 *
 * @param {HTMLElement} track
 */
function dispatchTransitionEnd(track) {
    const transitionEndEvent = new Event('transitionend', {bubbles: true});
    transitionEndEvent.propertyName = 'transform';
    track.dispatchEvent(transitionEndEvent);
}

/**
 * Spies on every assignment to `track.style.transform` from this point on, in order. Needed
 * because interrupting a pending wrap makes *two* writes in the same task (settle onto the real
 * slide, then animate to the new target) - asserting only the end state can't tell a correct
 * settle-then-animate sequence apart from a bug that animates from the stale, unsettled position
 * (both can land on the same final value - see #3595).
 *
 * @param {HTMLElement} track
 * @returns {string[]} The recorded values, in write order, mutated live as more happen.
 */
function recordTransformWrites(track) {
    const writes = [];
    let current = track.style.transform;
    Object.defineProperty(track.style, 'transform', {
        configurable: true,
        get() {
            return current;
        },
        set(value) {
            current = value;
            writes.push(value);
        },
    });

    return writes;
}

describe('$.fn.thumbnailCarousel (#3595)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('thumbnailCarousel_givenSingleSlide_addsNoArrowsAndLeavesTrackUninitialised', () => {
        const track = createCarousel(1);

        $(track).thumbnailCarousel();

        expect(document.querySelectorAll('.thumbnail-carousel__nav')).toHaveLength(0);
        expect(track.dataset.thumbnailCarousel).toBeUndefined();
        expect(document.querySelector('.thumbnail-carousel').getAttribute('role')).toBeNull();
        // No decorative clones either - the track is left completely untouched.
        expect(track.querySelectorAll('li')).toHaveLength(1);
    });

    test('thumbnailCarousel_givenSingleLazySlide_stillLoadsItsImage', () => {
        // The DataTables/handlebars markup lazy-loads every slide, including a lone one.
        document.body.innerHTML = `<div class="thumbnail-carousel">
            <ul class="thumbnail-carousel__track">
                <li class="thumbnail-carousel__slide"><img data-src="only.jpg" src="//:0"/></li>
            </ul>
        </div>`;
        const track = document.querySelector('.thumbnail-carousel__track');

        $(track).thumbnailCarousel();

        expect(slideSrc(track, 0)).toBe('only.jpg');
    });

    test('thumbnailCarousel_givenMultipleSlides_rendersLabelledPrevAndNextArrows', () => {
        const track = createCarousel(3);

        $(track).thumbnailCarousel({prevLabel: 'Vorige', nextLabel: 'Volgende'});

        expect(document.querySelector('.thumbnail-carousel__nav--prev').getAttribute('aria-label')).toBe('Vorige');
        expect(document.querySelector('.thumbnail-carousel__nav--next').getAttribute('aria-label')).toBe('Volgende');
        expect(document.querySelector('.thumbnail-carousel').getAttribute('aria-roledescription')).toBe('carousel');
        expect(document.querySelector('.thumbnail-carousel').getAttribute('aria-label')).toBe('Thumbnails');
        expect(realSlide(track, 1).getAttribute('aria-label')).toBe('2 / 3');
    });

    test('thumbnailCarousel_givenMultipleSlides_addsExactlyTwoDecorativeClonesAtTheEnds', () => {
        const track = createCarousel(3);

        $(track).thumbnailCarousel();

        expect(track.querySelectorAll('li')).toHaveLength(5);
        const startClone = track.children[0];
        const endClone = track.children[4];
        expect(startClone.getAttribute('aria-hidden')).toBe('true');
        expect(endClone.getAttribute('aria-hidden')).toBe('true');
        expect(startClone.hasAttribute('aria-label')).toBe(false);
        expect(endClone.hasAttribute('aria-label')).toBe(false);
    });

    test('thumbnailCarousel_givenMultipleSlides_bakesTheRealImageIntoBothDecorativeClones', () => {
        // The clones are static after init - if the last slide's (lazy) image weren't loaded
        // before cloning it, that clone would show the "//:0" placeholder forever.
        const track = createCarousel(5);

        $(track).thumbnailCarousel();

        expect(track.children[0].querySelector('img').getAttribute('src')).toBe('4.jpg');
        expect(track.children[6].querySelector('img').getAttribute('src')).toBe('0.jpg');
    });

    test('thumbnailCarousel_givenInitialised_marksOnlyTheFirstSlideVisible', () => {
        const track = createCarousel(3);

        $(track).thumbnailCarousel();

        expect(realSlide(track, 0).getAttribute('aria-hidden')).toBe('false');
        expect(realSlide(track, 1).getAttribute('aria-hidden')).toBe('true');
        expect(track.style.transform).toBe('translateX(-100%)');
    });

    test('thumbnailCarousel_givenNextClicked_advancesToTheSecondSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        $('.thumbnail-carousel__nav--next').trigger('click');

        expect(track.style.transform).toBe('translateX(-200%)');
        expect(realSlide(track, 1).getAttribute('aria-hidden')).toBe('false');
    });

    test('thumbnailCarousel_givenNextClickedOnLastSlide_animatesOntoTheEndCloneThenSnapsToTheRealFirstSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const $next = $('.thumbnail-carousel__nav--next');

        $next.trigger('click');
        $next.trigger('click');
        $next.trigger('click');

        // Mid-wrap: animating onto the end clone (slot slideCount + 1 = 4), not teleported
        // straight to the first slide. Nothing was inserted/removed - li count never changes.
        expect(track.style.transform).toBe('translateX(-400%)');
        expect(track.querySelectorAll('li')).toHaveLength(5);
        expect(realSlide(track, 0).getAttribute('aria-hidden')).toBe('false');

        dispatchTransitionEnd(track);

        // Snaps onto the real first slide's own slot (1) - same visual position as the clone,
        // since it's the same image, so nothing jumps.
        expect(track.style.transform).toBe('translateX(-100%)');
        expect(track.querySelectorAll('li')).toHaveLength(5);
    });

    test('thumbnailCarousel_givenPrevClickedOnFirstSlide_animatesOntoTheStartCloneThenSnapsToTheRealLastSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        $('.thumbnail-carousel__nav--prev').trigger('click');

        // Mid-wrap: animating onto the start clone (slot 0). Fully synchronous, single move() -
        // there's no DOM mutation for either wrap direction any more, so nothing to defer.
        expect(track.style.transform).toBe('translateX(0%)');
        expect(realSlide(track, 2).getAttribute('aria-hidden')).toBe('false');

        dispatchTransitionEnd(track);

        expect(track.style.transform).toBe('translateX(-300%)');
    });

    test('thumbnailCarousel_givenNextClickedRepeatedlyThroughAWrap_settlesOntoTheRealSlideBeforeAnimatingTheNewMove', () => {
        // A plain end-state assertion can't tell a correct "settle then animate" sequence apart
        // from the bug this covers (animating straight from the stale, unsettled clone slot) -
        // both can land on the same final transform. See recordTransformWrites().
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const $next = $('.thumbnail-carousel__nav--next');
        $next.trigger('click');
        $next.trigger('click');
        $next.trigger('click'); // wraps: currentIndex 0, mid-animation onto the end clone (slot 4)

        const writes = recordTransformWrites(track);
        $next.trigger('click'); // interrupts the still-unsettled wrap

        // First write: an instant, un-animated settle onto the real first slide's own slot (1) -
        // content-identical to the clone it was just showing, so nothing visibly jumps. Second
        // write: the animated move onto slot 2, i.e. one slide forward as the click asked for -
        // not a two-slot leap backward from the stale clone slot.
        expect(writes).toEqual(['translateX(-100%)', 'translateX(-200%)']);
        expect(track.style.transform).toBe('translateX(-200%)');

        // The wrap's pending snap was resolved (not just cancelled) along with it - its
        // transitionend listener is gone, so this is a no-op rather than yanking the transform.
        const transformBeforeStaleEvent = track.style.transform;
        dispatchTransitionEnd(track);
        expect(track.style.transform).toBe(transformBeforeStaleEvent);
    });

    test('thumbnailCarousel_givenDragStartedDuringAWrap_settlesOntoTheRealSlideBeforeThePreview', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        $('.thumbnail-carousel__nav--prev').trigger('click'); // wraps backward onto the start clone

        const writes = recordTransformWrites(track);
        // A drag starting here previews from `currentIndex + 1` (thumbnail-carousel.js); if
        // settle() hadn't run first, that preview would be offset by a whole slide from wherever
        // the track visually still is (the unsettled clone slot).
        drag(track, -10); // below the swipe threshold: previews, then snaps back without moving

        expect(writes[0]).toBe('translateX(-300%)'); // settle: real last slide's own slot (3)
        expect(track.style.transform).toBe('translateX(-300%)');
    });

    test('thumbnailCarousel_givenTheTransitionNeverFires_stillSettlesViaTheFallbackTimer', () => {
        // Covers `prefers-reduced-motion: reduce` (thumbnail-carousel.css sets transition: none),
        // where transitionend never fires.
        vi.useFakeTimers();
        try {
            const track = createCarousel(3);
            $(track).thumbnailCarousel();

            $('.thumbnail-carousel__nav--prev').trigger('click');
            vi.advanceTimersByTime(500);

            expect(track.style.transform).toBe('translateX(-300%)');
            expect(realSlide(track, 2).getAttribute('aria-hidden')).toBe('false');
        } finally {
            vi.useRealTimers();
        }
    });

    test('thumbnailCarousel_givenTwoSlides_wrapsInBothDirections', () => {
        const track = createCarousel(2);
        $(track).thumbnailCarousel();
        const $next = $('.thumbnail-carousel__nav--next');

        expect(track.querySelectorAll('li')).toHaveLength(4);

        // Slide 0 -> 1 is a plain move, not a wrap yet - only the second "next" wraps back to 0.
        $next.trigger('click');
        expect(track.style.transform).toBe('translateX(-200%)');

        $next.trigger('click');
        expect(track.style.transform).toBe('translateX(-300%)');
        dispatchTransitionEnd(track);
        expect(track.style.transform).toBe('translateX(-100%)');
        expect(track.querySelectorAll('li')).toHaveLength(4);

        $('.thumbnail-carousel__nav--prev').trigger('click');
        expect(track.style.transform).toBe('translateX(0%)');
        dispatchTransitionEnd(track);
        expect(track.style.transform).toBe('translateX(-200%)');
        expect(track.querySelectorAll('li')).toHaveLength(4);
    });

    test('thumbnailCarousel_givenAWrap_callsOnAfterSlideImmediatelyWithTheNewIndex', () => {
        // The index changes synchronously in move() for a wrap too - callers don't need to wait
        // for the snap-back to know which slide is now current.
        const track = createCarousel(3);
        const onAfterSlide = vi.fn();
        $(track).thumbnailCarousel({onAfterSlide});
        $('.thumbnail-carousel__nav--next').trigger('click');
        $('.thumbnail-carousel__nav--next').trigger('click');
        onAfterSlide.mockClear();

        $('.thumbnail-carousel__nav--next').trigger('click');

        expect(onAfterSlide).toHaveBeenCalledTimes(1);
        expect(onAfterSlide).toHaveBeenCalledWith(0, 3);

        onAfterSlide.mockClear();
        dispatchTransitionEnd(track);
        expect(onAfterSlide).not.toHaveBeenCalled();
    });

    test('thumbnailCarousel_givenNextClickedOnLastSlideWithoutLoop_staysOnTheLastSlide', () => {
        const track = createCarousel(2);
        $(track).thumbnailCarousel({loop: false});
        const $next = $('.thumbnail-carousel__nav--next');

        $next.trigger('click');
        $next.trigger('click');

        expect(track.style.transform).toBe('translateX(-200%)');
        expect($next.prop('disabled')).toBe(true);
        expect($('.thumbnail-carousel__nav--prev').prop('disabled')).toBe(false);
    });

    test('thumbnailCarousel_givenInitialised_loadsOnlyTheCurrentSlideAndItsNeighbours', () => {
        const track = createCarousel(5);

        $(track).thumbnailCarousel();

        expect(realSlideSrc(track, 1)).toBe('1.jpg');
        // The slide before the first one is the last one, a single loop-back away.
        expect(realSlideSrc(track, 4)).toBe('4.jpg');
        expect(realSlideSrc(track, 2)).toBe('//:0');
        expect(realSlideSrc(track, 3)).toBe('//:0');
    });

    test('thumbnailCarousel_givenDragPastThresholdAtTheLastSlideWithoutLoop_snapsBackToIt', () => {
        const track = createCarousel(2);
        $(track).thumbnailCarousel({loop: false});
        $('.thumbnail-carousel__nav--next').trigger('click');

        drag(track, -80);

        expect(track.style.transform).toBe('translateX(-200%)');
    });

    test('thumbnailCarousel_givenSlideChange_loadsTheUpcomingSlideImage', () => {
        const track = createCarousel(6);
        $(track).thumbnailCarousel();

        $('.thumbnail-carousel__nav--next').trigger('click');

        expect(realSlideSrc(track, 2)).toBe('2.jpg');
        expect(realSlide(track, 2).querySelector('img').hasAttribute('data-src')).toBe(false);
        expect(realSlideSrc(track, 3)).toBe('//:0');
    });

    test('thumbnailCarousel_givenArrowClicked_stopsThePropagationToSurroundingHandlers', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const cardClick = vi.fn();
        $('.thumbnail-carousel').parent().on('click', cardClick);

        $('.thumbnail-carousel__nav--next').trigger('click');

        expect(cardClick).not.toHaveBeenCalled();
    });

    test('thumbnailCarousel_givenArrowKeyOnNavButton_movesToTheAdjacentSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        $('.thumbnail-carousel__nav--next').trigger($.Event('keydown', {key: 'ArrowRight'}));

        expect(track.style.transform).toBe('translateX(-200%)');
    });

    test('thumbnailCarousel_givenDragPastTheThreshold_movesToTheNextSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        drag(track, -80);

        expect(track.style.transform).toBe('translateX(-200%)');
    });

    test('thumbnailCarousel_givenDragBelowTheThreshold_snapsBackToTheCurrentSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        drag(track, -10);

        expect(track.style.transform).toBe('translateX(-100%)');
    });

    test('thumbnailCarousel_givenTheBrowserCancelsTheDrag_abandonsItInsteadOfChangingSlide', () => {
        // pointercancel means the gesture was taken over (reclassified as a page scroll, say).
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        drag(track, -80, 'pointercancel');

        expect(track.style.transform).toBe('translateX(-100%)');
    });

    test('thumbnailCarousel_givenPointerDown_preventsTheBrowsersNativeImageDrag', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const pointerDown = $.Event('pointerdown', {button: 0, pointerId: 1, clientX: 200});

        $(track).trigger(pointerDown);

        expect(pointerDown.isDefaultPrevented()).toBe(true);
    });

    test('thumbnailCarousel_givenDragPastTheThreshold_swallowsTheClickThatFollowsIt', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const cardClick = vi.fn();
        $('.thumbnail-carousel').parent().on('click', cardClick);

        drag(track, -80);
        $(track.querySelector('img')).trigger('click');

        expect(cardClick).not.toHaveBeenCalled();
    });

    test('thumbnailCarousel_givenClickWithoutDragging_letsTheClickThrough', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const cardClick = vi.fn();
        $('.thumbnail-carousel').parent().on('click', cardClick);

        $(track.querySelector('img')).trigger('click');

        expect(cardClick).toHaveBeenCalledTimes(1);
    });

    test('thumbnailCarousel_givenSlideChange_callsOnAfterSlideWithTheNewIndex', () => {
        const track = createCarousel(3);
        const onAfterSlide = vi.fn();
        $(track).thumbnailCarousel({onAfterSlide});

        $('.thumbnail-carousel__nav--next').trigger('click');

        expect(onAfterSlide).toHaveBeenCalledWith(1, 3);
    });

    test('thumbnailCarousel_givenAlreadyInitialised_doesNotAddASecondSetOfArrowsOrClones', () => {
        // The discover page re-runs CarouselHandler over every batch of infinite-scroll results,
        // which re-matches the carousels it already initialised.
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        $(track).thumbnailCarousel();

        expect(document.querySelectorAll('.thumbnail-carousel__nav')).toHaveLength(2);
        expect(track.querySelectorAll('li')).toHaveLength(5);
    });
});
