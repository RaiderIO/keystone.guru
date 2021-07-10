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