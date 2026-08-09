// ---------------------------------------------------------------------------
// Covers $.fn.thumbnailCarousel (#3595), the self-owned replacement for the removed
// `lightslider` plugin. It turns the <ul> of route thumbnails on a dungeon route card
// into a 1-up carousel with prev/next arrows, pointer dragging and lazy-loaded
// off-screen images.
//
// jsdom has no layout (offsetWidth is always 0), so these assert the widget's state
// machine - index, transform, ARIA, image loading - rather than anything pixel-based.
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
 * Simulates the browser finishing the CSS transition beginWrap() animates onto the appended/
 * prepended clone - jsdom never runs real transitions, so nothing fires this on its own.
 *
 * @param {HTMLElement} track
 */
function dispatchTransitionEnd(track) {
    const transitionEndEvent = new Event('transitionend', {bubbles: true});
    transitionEndEvent.propertyName = 'transform';
    track.dispatchEvent(transitionEndEvent);
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
        expect(track.querySelectorAll('li')[1].getAttribute('aria-label')).toBe('2 / 3');
    });

    test('thumbnailCarousel_givenInitialised_marksOnlyTheFirstSlideVisible', () => {
        const track = createCarousel(3);

        $(track).thumbnailCarousel();

        const slides = track.querySelectorAll('li');
        expect(slides[0].getAttribute('aria-hidden')).toBe('false');
        expect(slides[1].getAttribute('aria-hidden')).toBe('true');
        expect(track.style.transform).toBe('translateX(0%)');
    });

    test('thumbnailCarousel_givenNextClicked_advancesToTheSecondSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        $('.thumbnail-carousel__nav--next').trigger('click');

        expect(track.style.transform).toBe('translateX(-100%)');
        expect(track.querySelectorAll('li')[1].getAttribute('aria-hidden')).toBe('false');
    });

    test('thumbnailCarousel_givenNextClickedOnLastSlide_animatesOntoAClonedFirstSlideThenSnapsToTheRealOne', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const $next = $('.thumbnail-carousel__nav--next');

        $next.trigger('click');
        $next.trigger('click');
        $next.trigger('click');

        // Mid-wrap: animating onto the appended clone, not teleported straight to the first slide.
        expect(track.style.transform).toBe('translateX(-300%)');
        expect(track.classList.contains('thumbnail-carousel__track--no-transition')).toBe(false);
        expect(track.querySelectorAll('li')).toHaveLength(4);

        dispatchTransitionEnd(track);

        expect(track.style.transform).toBe('translateX(0%)');
        expect(track.querySelectorAll('li')).toHaveLength(3);
        expect(track.querySelectorAll('li')[0].getAttribute('aria-hidden')).toBe('false');
    });

    test('thumbnailCarousel_givenPrevClickedOnFirstSlide_animatesOntoAClonedLastSlideThenSnapsToTheRealOne', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const $prev = $('.thumbnail-carousel__nav--prev');

        $prev.trigger('click');

        // The real slides silently shift one slot right to make room for the prepended clone
        // before the track animates left onto it - both invisible, since it's the same content.
        expect(track.style.transform).toBe('translateX(0%)');
        expect(track.querySelectorAll('li')).toHaveLength(4);

        dispatchTransitionEnd(track);

        expect(track.style.transform).toBe('translateX(-200%)');
        expect(track.querySelectorAll('li')).toHaveLength(3);
        expect(track.querySelectorAll('li')[2].getAttribute('aria-hidden')).toBe('false');
    });

    test('thumbnailCarousel_givenNextClickedWhileAWrapIsStillAnimating_ignoresItUntilTheWrapSettles', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();
        const $next = $('.thumbnail-carousel__nav--next');
        $next.trigger('click');
        $next.trigger('click');
        $next.trigger('click');

        $next.trigger('click');

        expect(track.querySelectorAll('li')).toHaveLength(4);
        expect(track.style.transform).toBe('translateX(-300%)');
    });

    test('thumbnailCarousel_givenNextClickedOnLastSlideWithoutLoop_staysOnTheLastSlide', () => {
        const track = createCarousel(2);
        $(track).thumbnailCarousel({loop: false});
        const $next = $('.thumbnail-carousel__nav--next');

        $next.trigger('click');
        $next.trigger('click');

        expect(track.style.transform).toBe('translateX(-100%)');
        expect($next.prop('disabled')).toBe(true);
        expect($('.thumbnail-carousel__nav--prev').prop('disabled')).toBe(false);
    });

    test('thumbnailCarousel_givenInitialised_loadsOnlyTheCurrentSlideAndItsNeighbours', () => {
        const track = createCarousel(5);

        $(track).thumbnailCarousel();

        expect(slideSrc(track, 1)).toBe('1.jpg');
        // The slide before the first one is the last one, a single loop-back away.
        expect(slideSrc(track, 4)).toBe('4.jpg');
        expect(slideSrc(track, 2)).toBe('//:0');
        expect(slideSrc(track, 3)).toBe('//:0');
    });

    test('thumbnailCarousel_givenDragPastThresholdAtTheLastSlideWithoutLoop_snapsBackToIt', () => {
        const track = createCarousel(2);
        $(track).thumbnailCarousel({loop: false});
        $('.thumbnail-carousel__nav--next').trigger('click');

        drag(track, -80);

        expect(track.style.transform).toBe('translateX(-100%)');
    });

    test('thumbnailCarousel_givenSlideChange_loadsTheUpcomingSlideImage', () => {
        const track = createCarousel(6);
        $(track).thumbnailCarousel();

        $('.thumbnail-carousel__nav--next').trigger('click');

        expect(slideSrc(track, 2)).toBe('2.jpg');
        expect(track.querySelectorAll('img')[2].hasAttribute('data-src')).toBe(false);
        expect(slideSrc(track, 3)).toBe('//:0');
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

        expect(track.style.transform).toBe('translateX(-100%)');
    });

    test('thumbnailCarousel_givenDragPastTheThreshold_movesToTheNextSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        drag(track, -80);

        expect(track.style.transform).toBe('translateX(-100%)');
    });

    test('thumbnailCarousel_givenDragBelowTheThreshold_snapsBackToTheCurrentSlide', () => {
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        drag(track, -10);

        expect(track.style.transform).toBe('translateX(0%)');
    });

    test('thumbnailCarousel_givenTheBrowserCancelsTheDrag_abandonsItInsteadOfChangingSlide', () => {
        // pointercancel means the gesture was taken over (reclassified as a page scroll, say).
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        drag(track, -80, 'pointercancel');

        expect(track.style.transform).toBe('translateX(0%)');
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

    test('thumbnailCarousel_givenAlreadyInitialised_doesNotAddASecondSetOfArrows', () => {
        // The discover page re-runs CarouselHandler over every batch of infinite-scroll results,
        // which re-matches the carousels it already initialised.
        const track = createCarousel(3);
        $(track).thumbnailCarousel();

        $(track).thumbnailCarousel();

        expect(document.querySelectorAll('.thumbnail-carousel__nav')).toHaveLength(2);
    });
});
