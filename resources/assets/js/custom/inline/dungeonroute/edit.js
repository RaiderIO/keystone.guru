class DungeonrouteEdit extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        let self = this;

        // Save settings in the modal
        $('#save_route_settings').bind('click', this._saveRouteSettings);

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

    _saveRouteSettings() {
        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}`,
            dataType: 'json',
            data: {
                dungeon_route_title: $('#dungeon_route_title').val(),
                teeming: $('#teeming').is(':checked') ? 1 : 0,
                attributes: $('#attributes').val(),
                faction_id: $('#faction_id').val(),
                seasonal_index: $('#seasonal_index').val(),
                specialization:
                    $('.specializationselect select').map(function () {
                        return $(this).val();
                    }).get()
                ,
                class:
                    $('.classselect select').map(function () {
                        return $(this).val();
                    }).get()
                ,
                race:
                    $('.raceselect select').map(function () {
                        return $(this).val();
                    }).get()
                ,
                unlisted: $('#unlisted').is(':checked') ? 1 : 0,
                demo: $('#demo').is(':checked') && isUserAdmin ? 1 : 0,
                affixes: $('#affixes').val(),
                _method: 'PATCH'
            },
            beforeSend: function () {
                $('#save_route_settings').hide();
                $('#save_route_settings_saving').show();
            },
            success: function (json) {
                showSuccessNotification(lang.get('messages.settings_saved'));

                let $seasonalIndex = $('#seasonal_index');
                if ($seasonalIndex.length > 0) {
                    getState().getMapContext().setSeasonalIndex(parseInt($seasonalIndex.val()));
                }
                getState().getMapContext().setTeeming($('#teeming').is(':checked'));
            },
            complete: function () {
                $('#save_route_settings').show();
                $('#save_route_settings_saving').hide();
            }
        });
    }

    cleanup() {
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister('killzone:enemyadded', this);
        killZoneMapObjectGroup.unregister('killzone:enemyremoved', this);
    }
}