// ---------------------------------------------------------------------------
// $.fn.thumbnailCarousel (#3595)
//
// Self-owned replacement for the unmaintained `lightslider` npm plugin (last npm
// release 2016), which drove exactly one widget: the per-floor thumbnail carousel
// on dungeon route cards. It is initialised on the <ul> track:
//
//     $('.thumbnail-carousel__track').thumbnailCarousel({prevLabel: '…', nextLabel: '…'});
//
// lightslider was configured with `item: 1, controls: false, pager: false,
// gallery: false, loop: true`, so all it really provided was a 1-up viewport you
// could drag through. Its lazy-loading and prev/next callbacks were dead code:
// the blades emitted eager `src` (never `data-src`), and `controls: false` meant
// the `.lSPrev`/`.lSNext` buttons those callbacks show/hide were never rendered.
// Both are reimplemented for real here, so multi-floor thumbnails are reachable
// without guessing that the image can be dragged.
//
// Layout is percentage-based (a flex track translated by whole multiples of 100%),
// so unlike lightslider this needs no width measurement and can safely be called
// before the images have loaded. Visuals live in
// resources/assets/css/lib/thumbnail-carousel.css.
// ---------------------------------------------------------------------------

const $ = require('jquery');

const defaults = {
    /** Wrap around at either end. Matches lightslider's `loop: true`. */
    loop: true,
    /** Horizontal drag distance (px) that commits to a slide change. lightslider's `swipeThreshold`. */
    swipeThreshold: 40,
    /** Accessible names for the arrow buttons; the caller passes translated strings. */
    prevLabel: 'Previous',
    nextLabel: 'Next',
    /** Called as onAfterSlide(index, slideCount) after every slide change. */
    onAfterSlide: null,
};

/**
 * Swaps an `data-src` placeholder image over to its real `src`. Removing the attribute
 * afterwards keeps this idempotent, so repeated slide changes don't re-assign the same URL.
 *
 * @param {HTMLElement} slide
 */
function loadSlideImages(slide) {
    slide.querySelectorAll('img[data-src]').forEach(function (img) {
        img.setAttribute('src', img.dataset.src);
        img.removeAttribute('data-src');
    });
}

/**
 * Turns each matched <ul> of <li> slides into a 1-up carousel with prev/next arrows,
 * pointer/touch dragging and lazy-loaded off-screen images.
 *
 * @param {{loop?: boolean, swipeThreshold?: number, prevLabel?: string, nextLabel?: string, onAfterSlide?: ?function(number, number): void}} [options]
 * @returns {jQuery}
 */
$.fn.thumbnailCarousel = function (options) {
    const settings = $.extend({}, defaults, options);

    return this.each(function () {
        const track = this;

        // Never initialise the same track twice - the discover page re-runs this over every
        // batch of infinite-scroll results, which re-matches the already-initialised cards.
        if (track.dataset.thumbnailCarousel === 'true') {
            return;
        }

        const slides = Array.from(track.children).filter(function (child) {
            return child.tagName === 'LI';
        });

        // A single thumbnail (or the dungeon-image fallback) has nothing to navigate: leave the
        // DOM completely untouched so those cards render exactly as they did without any JS.
        // The slide count is deliberately read from the DOM rather than the card's
        // `single`/`multiple` class, which is derived from the dungeon's floor count and can
        // disagree with the number of thumbnails that have actually been generated.
        if (slides.length < 2) {
            slides.forEach(loadSlideImages);

            return;
        }
        track.dataset.thumbnailCarousel = 'true';

        const $track = $(track);
        const $carousel = $track.parent();
        const slideCount = slides.length;

        $carousel.addClass('thumbnail-carousel--interactive').attr({
            role: 'group',
            'aria-roledescription': 'carousel',
        });

        slides.forEach(function (slide, index) {
            slide.setAttribute('role', 'group');
            slide.setAttribute('aria-roledescription', 'slide');
            slide.setAttribute('aria-label', `${index + 1} / ${slideCount}`);
        });

        const $prev = $('<button>', {
            type: 'button',
            'class': 'thumbnail-carousel__nav thumbnail-carousel__nav--prev',
            'aria-label': settings.prevLabel,
        }).append($('<i>', {'class': 'fas fa-chevron-left', 'aria-hidden': 'true'}));
        const $next = $('<button>', {
            type: 'button',
            'class': 'thumbnail-carousel__nav thumbnail-carousel__nav--next',
            'aria-label': settings.nextLabel,
        }).append($('<i>', {'class': 'fas fa-chevron-right', 'aria-hidden': 'true'}));

        $carousel.append($prev, $next);

        let currentIndex = 0;

        /**
         * Loads the current slide's images plus the next one's, so a slide change never waits
         * on a network request. This is what lightslider's onBeforeStart/onAfterSlide callbacks
         * were meant to do before the blades stopped emitting `data-src`.
         *
         * @param {number} index
         */
        function loadAround(index) {
            loadSlideImages(slides[index]);
            loadSlideImages(slides[(index + 1) % slideCount]);
        }

        /**
         * Paints the track at the current index. `animate` is false when wrapping around either
         * end, where animating would sweep backwards past every slide in between.
         *
         * @param {boolean} animate
         */
        function paint(animate) {
            track.classList.toggle('thumbnail-carousel__track--no-transition', !animate);
            if (!animate) {
                // Force a reflow so the class lands before the transform does; without it the
                // browser batches both into one frame and animates the jump anyway.
                void track.offsetHeight;
            }
            track.style.transform = `translateX(${currentIndex * -100}%)`;

            slides.forEach(function (slide, index) {
                slide.setAttribute('aria-hidden', String(index !== currentIndex));
            });

            if (!settings.loop) {
                $prev.prop('disabled', currentIndex === 0);
                $next.prop('disabled', currentIndex === slideCount - 1);
            }
        }

        /**
         * Moves the given number of slides, wrapping when `loop` is set and clamping otherwise.
         *
         * @param {number} delta
         */
        function move(delta) {
            const target = currentIndex + delta;
            const wrapped = target < 0 || target > slideCount - 1;

            if (wrapped && !settings.loop) {
                return;
            }

            currentIndex = (target + slideCount) % slideCount;
            loadAround(currentIndex);
            paint(!wrapped);

            if (typeof settings.onAfterSlide === 'function') {
                settings.onAfterSlide.call(track, currentIndex, slideCount);
            }
        }

        // These cards live inside click-sensitive containers (route table cells, the map's route
        // sidebar), so an arrow must never double as a click on whatever is behind it.
        $carousel.on('click', '.thumbnail-carousel__nav', function (clickEvent) {
            clickEvent.preventDefault();
            clickEvent.stopPropagation();

            move($(this).hasClass('thumbnail-carousel__nav--prev') ? -1 : 1);
        });

        // Arrow keys work while an arrow button has focus. The carousel itself deliberately
        // isn't focusable - it would add two tab stops to every card in a list of them.
        $carousel.on('keydown', '.thumbnail-carousel__nav', function (keyEvent) {
            if (keyEvent.key !== 'ArrowLeft' && keyEvent.key !== 'ArrowRight') {
                return;
            }

            keyEvent.preventDefault();
            move(keyEvent.key === 'ArrowLeft' ? -1 : 1);
        });

        let dragStartX = null;
        let dragDeltaX = 0;

        $track.on('pointerdown', function (pointerEvent) {
            // Ignore anything but a primary button press; a right-click drag isn't a swipe.
            if (pointerEvent.button !== 0) {
                return;
            }

            dragStartX = pointerEvent.clientX;
            dragDeltaX = 0;
            track.classList.add('thumbnail-carousel__track--no-transition');
            $carousel.addClass('thumbnail-carousel--dragging');

            if (typeof track.setPointerCapture === 'function') {
                track.setPointerCapture(pointerEvent.pointerId);
            }
        });

        $track.on('pointermove', function (pointerEvent) {
            if (dragStartX === null) {
                return;
            }

            dragDeltaX = pointerEvent.clientX - dragStartX;
            track.style.transform = `translateX(calc(${currentIndex * -100}% + ${dragDeltaX}px))`;
        });

        $track.on('pointerup pointercancel pointerleave', function (pointerEvent) {
            if (dragStartX === null) {
                return;
            }

            const delta = dragDeltaX;
            dragStartX = null;
            $carousel.removeClass('thumbnail-carousel--dragging');

            if (typeof track.releasePointerCapture === 'function' && track.hasPointerCapture?.(pointerEvent.pointerId)) {
                track.releasePointerCapture(pointerEvent.pointerId);
            }

            if (Math.abs(delta) > settings.swipeThreshold) {
                move(delta < 0 ? 1 : -1);
            } else {
                // Below the threshold: animate back to where we started.
                paint(true);
            }
        });

        // A drag that ends on top of an image still fires a click; swallow that one click so a
        // swipe can't be mistaken for a tap on whatever handler surrounds the card.
        $track.on('click', function (clickEvent) {
            if (Math.abs(dragDeltaX) > settings.swipeThreshold) {
                clickEvent.preventDefault();
                clickEvent.stopPropagation();
            }

            dragDeltaX = 0;
        });

        loadAround(currentIndex);
        paint(false);
    });
};
