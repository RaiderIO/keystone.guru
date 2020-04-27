class Sidebar {


    constructor(options) {
        this.options = options;
    }

    /**
     *
     */
    activate() {
        let self = this;

        // Make sure that the select options have a valid value
        this._refreshFloorSelect();

        $(this.options.switchDungeonFloorSelect).val(this.options.defaultSelectedFloorId);

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

        $sidebar.mCustomScrollbar({
            theme: 'minimal'
        });

        this._showSidebar();
    }

    /**
     * Refreshes the floor select and fills it with the floors that fit the currently selected dungeon.
     * @private
     */
    _refreshFloorSelect() {
        let $switchDungeonFloorSelect = $(this.options.switchDungeonFloorSelect);
        if ($switchDungeonFloorSelect.is('select')) {
            // Clear of all options
            $switchDungeonFloorSelect.find('option').remove();
            // Add each new floor to the select
            $.each(dungeonData.floors, function (index, floor) {
                // Reconstruct the dungeon floor select
                $switchDungeonFloorSelect.append($('<option>', {
                    text: floor.name,
                    value: floor.id
                }));
            });

            refreshSelectPickers();
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
        $sidebarToggle.attr('title', lang.get('messages.sidebar_expand'));
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
}