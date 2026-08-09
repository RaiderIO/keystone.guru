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
    /** Accessible names for the carousel and its arrow buttons; the caller passes translated strings. */
    label: 'Thumbnails',
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

        // `aria-roledescription` requires an accessible name to go with it, or screen readers
        // announce "carousel" with nothing to identify which one.
        $carousel.addClass('thumbnail-carousel--interactive').attr({
            role: 'group',
            'aria-roledescription': 'carousel',
            'aria-label': settings.label,
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
        // True while a loop-around wrap is animating; further move()/drag calls are ignored until
        // it settles, so a rapid double-click or drag can't race the clone/transitionend teardown.
        let wrapping = false;

        /**
         * Loads the current slide's images plus its two neighbours', so a slide change in either
         * direction never waits on a network request. This is what lightslider's
         * onBeforeStart/onAfterSlide callbacks were meant to do before the blades stopped emitting
         * `data-src`.
         *
         * @param {number} index
         */
        function loadAround(index) {
            loadSlideImages(slides[index]);
            loadSlideImages(slides[(index + 1) % slideCount]);
            loadSlideImages(slides[(index - 1 + slideCount) % slideCount]);
        }

        /**
         * Paints the track at the given visual position (defaults to `currentIndex`). The two
         * differ only mid-wrap, where `currentIndex` has already moved to the slide being
         * animated into view but the track still needs to slide past the clone beginWrap() append-
         * ed/prepended for it - see beginWrap().
         *
         * @param {boolean} animate
         * @param {number} [visualIndex]
         */
        function paint(animate, visualIndex = currentIndex) {
            track.classList.toggle('thumbnail-carousel__track--no-transition', !animate);
            if (!animate) {
                // Force a reflow so the class lands before the transform does; without it the
                // browser batches both into one frame and animates the jump anyway.
                void track.offsetHeight;
            }
            track.style.transform = `translateX(${visualIndex * -100}%)`;

            slides.forEach(function (slide, index) {
                slide.setAttribute('aria-hidden', String(index !== currentIndex));
            });

            if (!settings.loop) {
                $prev.prop('disabled', currentIndex === 0);
                $next.prop('disabled', currentIndex === slideCount - 1);
            }
        }

        /**
         * Animates a loop-around wrap (last slide -> first, or first -> last) instead of
         * teleporting straight to it. There's nothing to animate *into* past either end of the
         * track, so this temporarily appends/prepends a clone of the target slide for the track to
         * slide onto, then swaps back to the real slide - at the same visual position, so nothing
         * jumps - once the transition finishes.
         *
         * @param {number} delta +1 wraps forward past the last slide, -1 wraps backward past the first.
         */
        function beginWrap(delta) {
            const forward = delta > 0;
            const nextIndex = forward ? 0 : slideCount - 1;
            const target = slides[nextIndex];

            loadSlideImages(target);
            const clone = target.cloneNode(true);
            clone.setAttribute('aria-hidden', 'false');

            if (forward) {
                track.appendChild(clone);
            } else {
                track.insertBefore(clone, track.firstChild);
                // Prepending shifts every real slide's visual position one slot to the right;
                // silently correct for that before animating, so the insertion itself doesn't jump.
                paint(false, currentIndex + 1);
            }

            wrapping = true;
            currentIndex = nextIndex;
            // Forward: the clone sits right after the last real slide, at visual slot slideCount.
            // Backward: it was just prepended, so it's always at visual slot 0.
            paint(true, forward ? slideCount : 0);

            const onTransitionEnd = function (transitionEvent) {
                if (transitionEvent.target !== track || transitionEvent.propertyName !== 'transform') {
                    return;
                }

                track.removeEventListener('transitionend', onTransitionEnd);
                clone.remove();
                wrapping = false;
                paint(false);
                loadAround(currentIndex);

                if (typeof settings.onAfterSlide === 'function') {
                    settings.onAfterSlide.call(track, currentIndex, slideCount);
                }
            };

            track.addEventListener('transitionend', onTransitionEnd);
        }

        /**
         * Moves the given number of slides, wrapping when `loop` is set and clamping otherwise.
         *
         * @param {number} delta
         */
        function move(delta) {
            if (wrapping) {
                return;
            }

            const target = currentIndex + delta;
            const wrapped = target < 0 || target > slideCount - 1;

            if (wrapped && !settings.loop) {
                // Still repaint: a drag past the threshold at either end leaves the track sitting
                // at its dragged-out offset, so it has to be snapped back to the current slide.
                paint(true);

                return;
            }

            if (wrapped) {
                beginWrap(delta);

                return;
            }

            currentIndex = target;
            loadAround(currentIndex);
            paint(true);

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
            // Ignore anything but a primary button press; a right-click drag isn't a swipe. Also
            // ignore while a loop-around wrap is still animating - see move()/beginWrap().
            if (pointerEvent.button !== 0 || wrapping) {
                return;
            }

            // <img> is natively draggable, so without this every mouse drag on a thumbnail starts
            // HTML5 drag-and-drop instead - a ghost image follows the cursor and the UA cancels the
            // pointer sequence. CSS blocks the same thing on the touch/selection side.
            pointerEvent.preventDefault();

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
            // A cancelled pointer means the browser took the gesture over (it reclassified the
            // touch as a page scroll, say) - the swipe was abandoned, not completed, so snap back
            // however far it got.
            const completed = pointerEvent.type !== 'pointercancel';
            dragStartX = null;
            $carousel.removeClass('thumbnail-carousel--dragging');

            if (typeof track.releasePointerCapture === 'function' && track.hasPointerCapture?.(pointerEvent.pointerId)) {
                track.releasePointerCapture(pointerEvent.pointerId);
            }

            if (completed && Math.abs(delta) > settings.swipeThreshold) {
                move(delta < 0 ? 1 : -1);
            } else {
                // Abandoned, or below the threshold: animate back to where we started.
                dragDeltaX = 0;
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
