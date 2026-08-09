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
    /** Called as onAfterSlide(index, slideCount) as soon as the current slide's index changes -
     *  for a wrap that's immediately, not once the snap-back onto the real slide settles. */
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

        // A loop-around wrap needs somewhere to slide onto past either end of the track. This
        // used to insert/remove a real clone per wrap, but that raced the DOM mutation against
        // the transform snap-back and could paint one frame of blank track or the wrong slide in
        // between (#3595). Each end instead permanently carries a decorative clone of the
        // opposite slide, so a wrap is just a normal animated move (see move()) that lands one
        // slot short/long, then a plain transform-only snap - no DOM change, so no window for a
        // bad frame - puts the real slide back under it. Slot numbering: 0 is the start clone,
        // `i + 1` is real slide `i`, `slideCount + 1` is the end clone.
        loadSlideImages(slides[0]);
        loadSlideImages(slides[slideCount - 1]);
        const startClone = slides[slideCount - 1].cloneNode(true);
        const endClone = slides[0].cloneNode(true);
        [startClone, endClone].forEach(function (clone) {
            // Decorative only - screen readers should see exactly `slideCount` slides.
            clone.setAttribute('aria-hidden', 'true');
            clone.removeAttribute('aria-label');
        });
        track.insertBefore(startClone, slides[0]);
        track.appendChild(endClone);

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
        // The transitionend listener/fallback timer for an in-flight wrap's snap-back onto the
        // real slide, if one is pending - see scheduleSnap()/settle().
        let pendingSnap = null;

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
         * Paints the track at the given visual slot (defaults to the current slide's own slot,
         * `currentIndex + 1` - see the slot numbering note above). A wrap's move() call animates
         * onto slot `0` or `slideCount + 1` instead - one of the two decorative clones - so there's
         * somewhere to slide onto past either end.
         *
         * @param {boolean} animate
         * @param {number} [visualSlot]
         */
        function paint(animate, visualSlot = currentIndex + 1) {
            track.classList.toggle('thumbnail-carousel__track--no-transition', !animate);
            track.style.transform = `translateX(${visualSlot * -100}%)`;
            if (!animate) {
                // Force the reflow AFTER writing the transform, so the disabled transition and the
                // new value commit together as one un-animated frame. Forcing it before the write
                // (which is enough when nothing else follows in the same task) leaves this write
                // uncommitted for a paint(true, ...) that immediately follows - which would then
                // still animate from the stale pre-snap value instead of this one (#3595).
                void track.offsetHeight;
            }

            slides.forEach(function (slide, index) {
                slide.setAttribute('aria-hidden', String(index !== currentIndex));
            });

            if (!settings.loop) {
                $prev.prop('disabled', currentIndex === 0);
                $next.prop('disabled', currentIndex === slideCount - 1);
            }
        }

        /**
         * Resolves a wrap's pending snap-back onto the real slide immediately, if one exists -
         * restoring the invariant move()/pointerdown rely on, that the transform reflects
         * `currentIndex`'s own slot whenever nothing is animating. Without this, interrupting an
         * unsettled wrap computes its next move from the stale clone slot instead of the real one,
         * animating an extra slot in the wrong direction (#3595). No-ops if nothing is pending -
         * called unconditionally at the top of every move()/drag start, and is also what the wrap's
         * transitionend/fallback-timer triggers call once the animation has genuinely finished.
         */
        function settle() {
            if (pendingSnap === null) {
                return;
            }

            track.removeEventListener('transitionend', pendingSnap.onTransitionEnd);
            clearTimeout(pendingSnap.timer);
            pendingSnap = null;
            paint(false);
        }

        /**
         * After a wrap's move() animates onto a decorative clone slot, schedules settle() to snap -
         * no transition, and critically no DOM change, just the transform - back onto the real
         * slide's own slot once the reveal has visibly finished. That makes the swap atomic: there's
         * no window where the DOM and the transform disagree about which slide is showing, which is
         * what let a wrap paint one bad frame before (#3595).
         */
        function scheduleSnap() {
            function onTransitionEnd(transitionEvent) {
                if (transitionEvent.target !== track || transitionEvent.propertyName !== 'transform') {
                    return;
                }

                settle();
            }

            track.addEventListener('transitionend', onTransitionEnd);
            // `prefers-reduced-motion: reduce` (thumbnail-carousel.css) turns the transition off
            // entirely, so transitionend would never fire without this - 500ms comfortably clears
            // the 400ms transition with margin for slow devices.
            const timer = setTimeout(settle, 500);
            pendingSnap = {onTransitionEnd, timer};
        }

        /**
         * Moves the given number of slides, wrapping when `loop` is set and clamping otherwise.
         *
         * @param {number} delta
         */
        function move(delta) {
            settle();

            const target = currentIndex + delta;
            const wrapped = target < 0 || target > slideCount - 1;

            if (wrapped && !settings.loop) {
                // Still repaint: a drag past the threshold at either end leaves the track sitting
                // at its dragged-out offset, so it has to be snapped back to the current slide.
                paint(true);

                return;
            }

            // Computed from the *old* index: for a wrap this lands on the clone slot one past
            // either end (0 or slideCount + 1); otherwise it's just the next real slide's own slot.
            // settle() above guarantees currentIndex + 1 is where the track actually is right now.
            const visualSlot = currentIndex + 1 + delta;
            currentIndex = (target + slideCount) % slideCount;
            loadAround(currentIndex);
            paint(true, visualSlot);

            if (wrapped) {
                scheduleSnap();
            }

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

            // <img> is natively draggable, so without this every mouse drag on a thumbnail starts
            // HTML5 drag-and-drop instead - a ghost image follows the cursor and the UA cancels the
            // pointer sequence. CSS blocks the same thing on the touch/selection side.
            pointerEvent.preventDefault();

            // A drag starting mid-wrap must resolve the pending snap-back first (see settle()) -
            // both so it can't yank the transform out from under the user's finger once it fires,
            // and because the drag preview below assumes the transform already sits at
            // currentIndex's own slot.
            settle();

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
            track.style.transform = `translateX(calc(${(currentIndex + 1) * -100}% + ${dragDeltaX}px))`;
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
