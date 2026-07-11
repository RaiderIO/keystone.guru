class CommonGeneralMenuitemsanchor extends InlineCode {
    activate() {
        super.activate();

        const anchor = window.location.hash;
        if (typeof anchor !== 'undefined') {
            let tabTrigger = document.querySelector(`a[href="${anchor}"]`);
            if (tabTrigger !== null) {
                bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
            }
        }

        // When you click a nav, update the hash
        $('ul.nav li a').unbind('click').bind('click', function () {
            window.location.hash = $(this).attr('href').replace('#', '');
        });
    }
}
