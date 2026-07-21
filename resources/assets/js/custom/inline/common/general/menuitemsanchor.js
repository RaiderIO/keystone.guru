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

        // When you click a nav, update the hash without scrolling the page to the anchor
        $('ul.nav li a').unbind('click').bind('click', function () {
            history.replaceState(undefined, undefined, $(this).attr('href'));
        });
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonGeneralMenuitemsanchor};
}
