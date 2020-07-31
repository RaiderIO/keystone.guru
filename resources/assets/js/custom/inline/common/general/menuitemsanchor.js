class CommonGeneralMenuitemsanchor extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        const anchor = window.location.hash;
        if (typeof anchor !== 'undefined') {
            $(`a[href="${anchor}"]`).tab('show')
        }

        // When you click a nav, update the hash
        $('ul.nav li a').bind('click', function () {
            window.location.hash = $(this).attr('href').replace('#', '');
        });
    }
}