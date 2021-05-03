class SidebarNavigation extends Sidebar {

    constructor(options) {
        super(options);

        this._floorIdChangeSource = null;
    }

    /**
     *
     */
    activate() {
        super.activate();

        let self = this;

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

    cleanup() {
        super.cleanup();
        // After load complete, properly toggle the visibility. Then all layers get toggled properly
        getState().getDungeonMap().unregister('map:mapobjectgroupsloaded', this);
    }
}