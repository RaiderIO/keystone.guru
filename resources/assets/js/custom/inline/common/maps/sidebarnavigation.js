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

        // Copy to clipboard functionality
        $('#map_shareable_link_copy_to_clipboard').bind('click', function () {
            copyToClipboard($('#map_shareable_link').val());
        });
        $('#map_embedable_link_copy_to_clipboard').bind('click', function () {
            copyToClipboard($('#map_embedable_link').val());
        });
        $('.copy_mdt_string_to_clipboard').bind('click', function () {
            let $exportResult = $('#mdt_export_result');
            copyToClipboard($exportResult.val(), $exportResult);
        });
        $('#map_mdt_export').bind('click', function () {
            self._fetchMdtExportString();
        });

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

        // Map object group visibility
        let $mapObjectGroupVisibilitySelect = $('#map_map_object_group_visibility');

        let map = getState().getDungeonMap();
        // After load complete, properly toggle the visibility. Then all layers get toggled properly
        map.register('map:mapobjectgroupsloaded', this, this._mapObjectGroupVisibilityChanged);

        let mapObjectGroups = map.mapObjectGroupManager.mapObjectGroups;
        let cookieHiddenMapObjectGroups = JSON.parse(Cookies.get('hidden_map_object_groups'));

        for (let i in mapObjectGroups) {
            if (mapObjectGroups.hasOwnProperty(i)) {
                let group = mapObjectGroups[i];

                // If any of the names of a map object group is found in the array, hide the selection
                let selected = {selected: 'selected'};
                for (let index in group.names) {
                    if (group.names.hasOwnProperty(index) && cookieHiddenMapObjectGroups.includes(group.names[index])) {
                        selected = {};
                        break;
                    }
                }

                $mapObjectGroupVisibilitySelect.append($('<option>', $.extend(selected, {
                    text: lang.get(`messages.${group.names[0]}_map_object_group_label`),
                    value: group.names[0]
                })));
            }
        }

        // Trigger the change event now to initialize the map object groups
        $mapObjectGroupVisibilitySelect.bind('change', this._mapObjectGroupVisibilityChanged);
        this._mapObjectGroupVisibilityChanged();
    }

    /**
     *
     * @private
     */
    _fetchMdtExportString() {
        $.ajax({
            type: 'GET',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/mdtExport`,
            dataType: 'json',
            beforeSend: function () {
                $('.mdt_export_loader_container').show();
                $('.mdt_export_result_container').hide();
            },
            success: function (json) {
                $('#mdt_export_result').val(json.mdt_string);

                // Inject the warnings, if there are any
                if (json.warnings.length > 0) {
                    (new MdtStringWarnings(json.warnings))
                        .render($('#mdt_export_result_warnings'));
                }

            },
            complete: function () {
                $('.mdt_export_loader_container').hide();
                $('.mdt_export_result_container').show();
            }
        });
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
     *
     * @private
     */
    _mapObjectGroupVisibilityChanged() {
        let $mapObjectGroupVisibilitySelect = $('#map_map_object_group_visibility');
        if ($mapObjectGroupVisibilitySelect.length > 0) {
            let selected = $mapObjectGroupVisibilitySelect.val();

            // Make a copy so we don't modify the OG array
            let toHide = MAP_OBJECT_GROUP_NAMES.slice();
            // Show everything that needs to be shown
            for (let i = 0; i < selected.length; i++) {
                let name = selected[i];
                let group = getState().getDungeonMap().mapObjectGroupManager.getByName(name);
                group.setVisibility(true);

                // Remove it from the toHide list
                toHide.splice(toHide.indexOf(name), 1);
            }

            // Update our cookie so that we know upon page refresh
            Cookies.set('hidden_map_object_groups', toHide);

            // Hide everything that needs to be hidden
            for (let index in toHide) {
                if (toHide.hasOwnProperty(index)) {
                    let group = getState().getDungeonMap().mapObjectGroupManager.getByName(toHide[index]);
                    group.setVisibility(false);
                }
            }
        }
    }

    cleanup() {
        super.cleanup();
        // After load complete, properly toggle the visibility. Then all layers get toggled properly
        getState().getDungeonMap().unregister('map:mapobjectgroupsloaded', this);
    }
}