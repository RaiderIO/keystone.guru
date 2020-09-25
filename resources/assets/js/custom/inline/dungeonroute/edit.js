class DungeonrouteEdit extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        let self = this;

        // Save settings in the modal
        $('#save_route_settings').bind('click', this._saveRouteSettings);

        $('#map_route_publish').bind('click', function () {
            self._setPublished(true);
        });

        $('#map_route_unpublish').bind('click', function () {
            self._setPublished(false);
        });

        this._refreshRoutePublishButton();
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('killzone:enemyadded', this, this._refreshRoutePublishButton.bind(this));
        killZoneMapObjectGroup.register('killzone:enemyremoved', this, this._refreshRoutePublishButton.bind(this));
    }

    _refreshRoutePublishButton() {
        let $mapRoutePublish = $('#map_route_publish');

        // Remove disabled from the
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        $mapRoutePublish.attr('disabled', !killZoneMapObjectGroup.hasKilledAllUnskippables());
    }

    _setPublished(value) {
        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/${value ? 'publish' : 'unpublish'}`,
            dataType: 'json',
            data: {
                published: value === true ? 1 : 0
            },
            beforeSend: function () {
                $('#map_route_publish').css('pointer-events', 'none');
                $('#map_route_unpublish').css('pointer-events', 'none');
            },
            success: function (json) {
                if (value) {
                    // Published
                    $('#map_route_publish').addClass('d-none');
                    $('#map_route_unpublish').removeClass('d-none');

                    showSuccessNotification(lang.get('messages.route_published'));
                } else {
                    // Unpublished
                    $('#map_route_publish').removeClass('d-none');
                    $('#map_route_unpublish').addClass('d-none');

                    showWarningNotification(lang.get('messages.route_unpublished'));
                }
            },
            complete: function () {
                $('#map_route_publish').css('pointer-events', 'auto');
                $('#map_route_unpublish').css('pointer-events', 'auto');
            }
        });
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

                getState().setSeasonalIndex(parseInt($('#seasonal_index').val()));
                getState().setTeeming($('#teeming').is(':checked'));
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