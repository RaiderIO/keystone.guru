class Sidebar {


    constructor(options) {
        this.options = options;

        this._floorIdChangeSource = null;
    }

    /**
     *
     */
    activate() {
        let self = this;

        if (isMobile()) {
            let dungeonMap = getState().getDungeonMap();
            let fn = function () {
                self._hideSidebar();
            };
            dungeonMap.leafletMap.off('move', fn);
            dungeonMap.leafletMap.on('move', fn);
        }

        // Register for external changes so that we update our dropdown
        getState().register('floorid:changed', this, function (floorIdChangedEvent) {
            if (self._floorIdChangeSource === null) {
                self._floorIdChangeSource = 'external';

                $(self.options.switchDungeonFloorSelect).val(floorIdChangedEvent.data.floorId);
                self._floorIdChangeSource = null;
            }

            let pathname = window.location.pathname;
            let pathSplit = trimEnd(pathname, '/').split('/');
            let newUrl = window.location.protocol + '//' + window.location.host;

            if (getState().isMapAdmin()) {
                // Example url: https://keystone.test/admin/dungeon/14/floor/42/mapping
                // Strip the last two elements (<number>/mapping)
                pathSplit.splice(-2);
                pathname = pathSplit.join('/');
                newUrl += `${pathname}/${floorIdChangedEvent.data.floorId}/mapping`;
            } else {
                // Example url: https://keystone.test/bbzlbOX, https://keystone.test/bbzlbOX/2 (last integer is optional)
                if (isNumeric(pathSplit[pathSplit.length - 1])) {
                    // Strip the last two elements (<number>/mapping)
                    pathSplit.splice(-1);
                    pathname = pathSplit.join('/');
                }
                newUrl += `${pathname}/${getState().getCurrentFloor().index}`;
            }


            history.pushState({page: 1},
                newUrl,
                newUrl);

            // Make sure that the sidebar's select picker gets updated with the newly selected value
            refreshSelectPickers();
        });

        $(this.options.switchDungeonFloorSelect).change(function () {
            if (self._floorIdChangeSource === null) {
                self._floorIdChangeSource = 'select';

                // Pass the new floor ID to the map
                getState().setFloorId($(self.options.switchDungeonFloorSelect).val());
                self._floorIdChangeSource = null;
            }
        });

        // Make sure that the select options have a valid value
        this._refreshFloorSelect();

        // Switch floors
        $(this.options.switchDungeonFloorSelect).val(this.options.defaultSelectedFloorId);

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
            $.each(getState().getMapContext().getDungeon().floors, function (index, floor) {
                // Reconstruct the dungeon floor select
                $switchDungeonFloorSelect.append($('<option>', {
                    text: floor.name,
                    value: floor.id
                }));
            });

            // Now handled by dungeonmap refresh
            // refreshSelectPickers();
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

    cleanup() {

    }
}