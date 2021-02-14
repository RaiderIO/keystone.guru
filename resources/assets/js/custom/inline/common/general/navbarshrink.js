class CommonGeneralNavbarshrink extends InlineCode {
    /**
     * https://www.codeply.com/go/UGAuToRipu/bootstrap-4-shrink-nav-on-scroll
     */
    activate() {
        super.activate();

        let self = this;

        $('[data-toggle="navbar-shrink"]').each(function () {
            let ele = $(this);

            $(window).on('scroll resize', function () {
                self.toggleAffix(ele, $(this));
            });

            // init
            self.toggleAffix(ele, $(window));
        });
    }

    /**
     *
     * @param shrinkElement
     * @param scrollElement
     */
    toggleAffix(shrinkElement, scrollElement) {
        // The element that takes up the space that the fixed-top navbar leaves because it floats
        let spacer = $('.navbar-top-fixed-spacer');
        let top = spacer.offset().top;


        if (scrollElement.scrollTop() > top) {
            shrinkElement.addClass('navbar-shrink');
        } else {
            shrinkElement.removeClass('navbar-shrink');
        }

        let height = shrinkElement.outerHeight();
        spacer.height(height);
    }
}