class SidebarNavigation extends Sidebar {

    constructor(options) {
        super(options);
    }

    /**
     *
     */
    activate() {
        super.activate();

        // Map object group visibility
        let $mapObjectGroupVisibilitySelect = $('#map_map_object_group_visibility');

        let map = getState().getDungeonMap();
        // After load complete, properly toggle the visibility. Then all layers get toggled properly
        map.register('map:mapobjectgroupsfetchsuccess', this, this._mapObjectGroupVisibilityChanged);

        let mapObjectGroups = map.mapObjectGroupManager.mapObjectGroups;
        let cookieHiddenMapObjectGroups = Cookies.get('hidden_map_object_groups');

        for (let i in mapObjectGroups) {
            if (mapObjectGroups.hasOwnProperty(i)) {
                let group = mapObjectGroups[i];

                let selected = !cookieHiddenMapObjectGroups.includes(group.name) ? {selected: 'selected'} : {};
                $mapObjectGroupVisibilitySelect.append($('<option>', $.extend(selected, {
                    text: lang.get(`messages.${group.name}_map_object_group_label`),
                    value: group.name
                })));
            }
        }

        $mapObjectGroupVisibilitySelect.bind('change', this._mapObjectGroupVisibilityChanged);
    }

    _mapObjectGroupVisibilityChanged() {
        let $mapObjectGroupVisibilitySelect = $('#map_map_object_group_visibility');
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

    cleanup() {
        super.cleanup();
        // After load complete, properly toggle the visibility. Then all layers get toggled properly
        getState().getDungeonMap().unregister('map:mapobjectgroupsfetchsuccess', this);
    }
}