class SettingsTabRoute extends SettingsTab {

    constructor(options) {
        super(options);

    }

    activate() {

        if (this.options.hasOwnProperty('dungeonroute') && this.options.dungeonroute !== null) {
            // Level
            (new KeyLevelHandler(this.options.levelMin, this.options.levelMax)
                .apply('#dungeon_route_level', {
                    from: this.options.dungeonroute.level_min,
                    to: this.options.dungeonroute.level_max,
                }));

            // Save settings in the modal
            $('#save_route_settings').unbind('click').bind('click', this._saveRouteSettings);
        }
    }

    /**
     *
     * @private
     */
    _saveRouteSettings() {
        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}`,
            dataType: 'json',
            data: {
                team_id: $('#team_id_select').val(),
                dungeon_route_title: $('#dungeon_route_title').val(),
                dungeon_route_description: c.map.sanitizeText($('#dungeon_route_description').val()),
                dungeon_route_level: $('#dungeon_route_level').val(),
                // teeming: $('#teeming').is(':checked') ? 1 : 0,
                attributes: $('#attributes').val(),
                faction_id: $('#faction_id').val(),
                seasonal_index: $('#seasonal_index').val(),
                class:
                    $('.classselect select').map(function () {
                        return $(this).val();
                    }).get()
                ,
                specialization:
                    $('.specializationselect select').map(function () {
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
                route_select_affixes: $('#route_select_affixes').val(),
                _method: 'PATCH'
            },
            beforeSend: function () {
                $('#save_route_settings').hide();
                $('#save_route_settings_saving').show();
            },
            success: function (json) {
                showSuccessNotification(lang.get('messages.settings_saved'));

                let $title = $('#route_title');
                if ($title.length > 0) {
                    $title.html(json.title);
                }

                let mapContext = getState().getMapContext();

                let $seasonalIndex = $('#seasonal_index');
                if ($seasonalIndex.length > 0) {
                    mapContext.setSeasonalIndex(parseInt($seasonalIndex.val()));
                }
                let $teeming = $('#teeming');
                if ($teeming.length > 0) {
                    mapContext.setTeeming($teeming.is(':checked'));
                }

                let levelSplit = $('#dungeon_route_level').val().split(';');

                mapContext.setLevelMin(levelSplit[0]);
                mapContext.setLevelMax(levelSplit[1]);
                mapContext.setDescription(json.description);

                $('#killzones_description').html(json.description);
            },
            complete: function () {
                $('#save_route_settings').show();
                $('#save_route_settings_saving').hide();
            }
        });
    }

}
