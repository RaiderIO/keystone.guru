class DungeonrouteEdit extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        this._refreshRoutePublishButton();
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('killzone:enemyadded', this, this._refreshRoutePublishButton.bind(this));
        killZoneMapObjectGroup.register('killzone:enemyremoved', this, this._refreshRoutePublishButton.bind(this));
    }

    /**
     *
     * @private
     */
    _refreshRoutePublishButton() {
        let $mapRoutePublish = $('#map_route_publish');

        // Remove disabled from the
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let hasKilledAllUnskippables = killZoneMapObjectGroup.hasKilledAllUnskippables();
        $mapRoutePublish.attr('disabled', !hasKilledAllUnskippables);

        $('#map_route_publish_container')
            .attr('data-toggle', 'tooltip')
            .attr('title', hasKilledAllUnskippables ? '' : lang.get('messages.cannot_change_sharing_settings_not_all_unkillables_killed'))
            .refreshTooltips();
    }

    cleanup() {
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister('killzone:enemyadded', this);
        killZoneMapObjectGroup.unregister('killzone:enemyremoved', this);
    }
}