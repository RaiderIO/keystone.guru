class Sidebar {


    constructor(options) {
        this.options = options;
    }

    /**
     *
     */
    activate() {
        let self = this;

        if (this.options.hideOnMove) {
            let dungeonMap = getState().getDungeonMap();
            let fn = function () {
                self.hideSidebar();
            };
            dungeonMap.leafletMap.off('move', fn);
            dungeonMap.leafletMap.on('move', fn);
        }

        // Sidebar toggle
        let $sidebar = $(this.options.sidebarSelector);
        $(this.options.sidebarToggleSelector).on('click', function () {
            // Dismiss
            if ($sidebar.hasClass('active')) {
                self.hideSidebar();
            }
            // Show
            else {
                self.showSidebar();
            }

            refreshTooltips();
        });

        // If we don't have a default state set, restore the state from a cookie
        if (typeof this.options.defaultState === 'undefined' || this.options.defaultState === null) {
            if (this._getCookieValue()) {
                this.showSidebar()
            } else {
                this.hideSidebar();
            }
        }
        // A default state overrides the cookie preferences
        else if (this.options.defaultState === 1) {
            // But if we forcibly show the sidebar, do not save it in the cookie but remember the previous value instead
            this.showSidebar(true);
        } else if (this.options.defaultState === 0) {
            this.hideSidebar();
        }
    }

    /**
     *
     * @returns {*}
     * @private
     */
    _getCookieValue() {
        return Cookies.get(this.options.stateCookie) ?? this.options.defaultState ?? 1;
    }

    /**
     *
     * @param {Boolean} value
     * @private
     */
    _setCookie(value) {
        // Save the state
        Cookies.set(this.options.stateCookie, value ? 1 : 0);
    }

    /**
     * Hides the sidebar from view.
     */
    hideSidebar() {
        let $sidebar = $(this.options.sidebarSelector);
        let $sidebarToggle = $(this.options.sidebarToggleSelector);

        // Hide sidebar
        $sidebar.removeClass('active');
        // Move toggle button back
        $sidebarToggle.removeClass('active');
        $sidebarToggle.attr('title', lang.get('messages.sidebar_expand'));
        // Toggle image
        if (this.options.anchor === 'left') {
            $sidebarToggle.find('i').removeClass('fa-arrow-left').addClass('fa-arrow-right');
        } else {
            $sidebarToggle.find('i').removeClass('fa-arrow-right').addClass('fa-arrow-left');
        }

        this._setCookie(false);
    }

    /**
     * Shows the sidebar.
     */
    showSidebar(ignoreCookie = false) {
        let $sidebar = $(this.options.sidebarSelector);
        let $sidebarToggle = $(this.options.sidebarToggleSelector);

        // Open sidebar
        $sidebar.addClass('active');
        // Move toggle button
        $sidebarToggle.addClass('active');
        $sidebarToggle.attr('title', lang.get('messages.sidebar_collapse'));
        // Toggle image
        if (this.options.anchor === 'left') {
            $sidebarToggle.find('i').removeClass('fa-arrow-right').addClass('fa-arrow-left');
        } else {
            $sidebarToggle.find('i').removeClass('fa-arrow-left').addClass('fa-arrow-right');
        }

        if (!ignoreCookie) {
            this._setCookie(true);
        }
    }

    cleanup() {

    }
}
