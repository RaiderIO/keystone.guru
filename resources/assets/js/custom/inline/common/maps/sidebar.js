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
                self._hideSidebar();
            };
            dungeonMap.leafletMap.off('move', fn);
            dungeonMap.leafletMap.on('move', fn);
        }

        // Sidebar toggle
        let $sidebar = $(this.options.sidebarSelector);
        $(this.options.sidebarToggleSelector).on('click', function () {
            // Dismiss
            if ($sidebar.hasClass('active')) {
                self._hideSidebar();
            }
            // Show
            else {
                self._showSidebar();
            }

            refreshTooltips();
        });

        if (this.options.defaultState) {
            this._showSidebar();
        }
    }

    /**
     * Hides the sidebar from view.
     * @private
     */
    _hideSidebar() {
        let $sidebar = $(this.options.sidebarSelector);
        let $sidebarToggle = $(this.options.sidebarToggleSelector);

        // Hide sidebar
        $sidebar.removeClass('active');
        // Move toggle button back
        $sidebarToggle.removeClass('active');
        // $sidebarToggle.attr('title', lang.get('messages.sidebar_expand'));
        // Toggle image
        if (this.options.anchor === 'left') {
            $sidebarToggle.find('i').removeClass('fa-arrow-left').addClass('fa-arrow-right');
        } else {
            $sidebarToggle.find('i').removeClass('fa-arrow-right').addClass('fa-arrow-left');
        }
    }

    /**
     * Shows the sidebar.
     * @private
     */
    _showSidebar() {
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
    }

    cleanup() {

    }
}
