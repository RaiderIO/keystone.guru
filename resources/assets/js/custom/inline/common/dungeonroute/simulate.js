class CommonDungeonrouteSimulate extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        // Copy to clipboard functionality
        $('.copy_simulationcraft_string_to_clipboard').unbind('click').bind('click', function () {
            let $exportResult = $('#mdt_export_result');
            copyToClipboard($exportResult.val(), $exportResult);
        });

        $('#simulate_get_string').unbind('click').bind('click', this._fetchSimulationCraftString.bind(this));
        $('#simulate_modal').on('show.bs.modal', this._simulateModalOpened.bind(this));

        // Whenever something changes in the screen, save the settings so that on page refresh we still remember it
        for (let key in this._getData()) {
            $(`#simulate_${key}`).on('change', this._saveSettings.bind(this));
        }
        this._loadSettings();
    }

    /**
     *
     * @returns {object}
     * @private
     */
    _getData() {
        return {
            key_level: $('#simulate_key_level').val(),
            shrouded_bounty_type: $('#simulate_shrouded_bounty_type').val(),
            affix: $('#simulate_affix').val(),
            bloodlust: $('#simulate_bloodlust').is(':checked') ? 1 : 0,
            arcane_intellect: $('#simulate_arcane_intellect').is(':checked') ? 1 : 0,
            power_word_fortitude: $('#simulate_power_word_fortitude').is(':checked') ? 1 : 0,
            battle_shout: $('#simulate_battle_shout').is(':checked') ? 1 : 0,
            mystic_touch: $('#simulate_mystic_touch').is(':checked') ? 1 : 0,
            chaos_brand: $('#simulate_chaos_brand').is(':checked') ? 1 : 0,
            skill_loss_percent: $('#simulate_skill_loss_percent').val(),
            hp_percent: $('#simulate_hp_percent').val(),
        };
    }

    /**
     *
     * @private
     */
    _loadSettings() {
        let settingsString = Cookies.get('simulate_modal_settings');
        if (typeof settingsString === 'string') {
            let settings = JSON.parse(settingsString);
            for (let key in settings) {
                // Restore everything to how it was
                let $input = $(`#simulate_${key}`);
                if ($input.is(':checkbox')) {
                    if (parseInt(settings[key]) === 1) {
                        $input.attr('checked', 'checked');
                    } else {
                        $input.removeAttr('checked');
                    }
                } else {
                    $input.val(settings[key]);
                }
            }
        }
    }

    /**
     *
     * @private
     */
    _saveSettings() {
        Cookies.set('simulate_modal_settings', JSON.stringify(this._getData()));
    }

    /**
     *
     * @private
     */
    _simulateModalOpened() {
        let self = this;

        let $keyLevelSlider = $('#simulate_key_level')
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 2,
                max: 40
            });

        let $skillLossPercent = $('#simulate_skill_loss_percent')
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 0,
                max: 100
            });

        let $hpPercentage = $('#simulate_hp_percent')
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 0,
                max: 100
            });
    }

    /**
     *
     * @private
     */
    _fetchSimulationCraftString() {
        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/simulate`,
            dataType: 'json',
            data: this._getData(),
            beforeSend: function () {
                $('.simulationcraft_export_loader_container').show();
                $('.simulationcraft_export_result_container').hide();
            },
            success: function (json) {
                $('#simulationcraft_export_result').val(json.string);
            },
            complete: function () {
                $('.simulationcraft_export_loader_container').hide();
                $('.simulationcraft_export_result_container').show();
            }
        });
    }
}
