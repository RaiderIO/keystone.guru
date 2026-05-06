class CommonGeneralNavbarshrink extends InlineCode {
    /**
     * https://www.codeply.com/go/UGAuToRipu/bootstrap-4-shrink-nav-on-scroll
     */
    activate() {
        super.activate();

        this.fixedHeaderHeight = parseInt($('.navbar-first').css('height'));
        this.fixedSpacerDefaultHeight = parseInt($('.navbar-second').css('height'));
        this._recentElementEvents = new WeakMap();

        let self = this;

        $('[data-toggle="navbar-shrink"]').each(function () {
            let ele = $(this);

            $(window).on('scroll resize', function (event) {
                self.resizeShrinkElement(event, ele, $(this));
            });

            // init
            self.resizeShrinkElement(null, ele, $(window));
        });
    }

    /**
     * Prevents repeated event handling for the same element + event type within a short period.
     *
     * @param {jQuery} $element
     * @param {string} eventName
     * @param {number} thresholdMs
     * @returns {boolean}
     * @private
     */
    _isRecentSimilarEvent($element, eventName, thresholdMs = 500) {
        if (!$element || $element.length === 0) {
            return false;
        }

        const element = $element.get(0);
        let eventTimes = this._recentElementEvents.get(element);

        if (!eventTimes) {
            eventTimes = {};
            this._recentElementEvents.set(element, eventTimes);
        }

        const now = Date.now();
        const lastTriggeredAt = eventTimes[eventName] || 0;

        if (now - lastTriggeredAt < thresholdMs) {
            return true;
        }

        eventTimes[eventName] = now;
        return false;
    }

    /**
     *
     * @param event
     * @param shrinkElement
     * @param scrollElement
     */
    resizeShrinkElement(event, shrinkElement, scrollElement) {
        // The element that takes up the space that the fixed-top navbar leaves because it floats
        let spacer = $('.navbar-top-fixed-spacer');
        let top = spacer.offset().top;

        if ((scrollElement.scrollTop()) > top) {
            // Prevent one event triggering another, on repeat, by consuming events if necessary
            if (event !== null && this._isRecentSimilarEvent(shrinkElement, event.type, 500)) {
                return;
            }

            shrinkElement.addClass('navbar-shrink');
            spacer.height(parseInt(shrinkElement.outerHeight()));
        } else {
            shrinkElement.removeClass('navbar-shrink');
            spacer.height(this.fixedSpacerDefaultHeight + this.fixedHeaderHeight);
        }
    }
}
