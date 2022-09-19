class CommonDungeonrouteSimulate extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        this._initialized = false;

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

        //
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
            hp_percent: $('#simulate_hp_percent').val(),
            ranged_pull_compensation_yards: $('#simulate_ranged_pull_compensation_yards').val(),
            use_mounts: $('#simulate_use_mounts').is(':checked') ? 1 : 0,
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

        // If not filled yet, fill the bloodlust per pull selector
        let $bloodlustPerPullSelect = $('#simulate_bloodlust_per_pull');
        // Either didn't have any options yet, or none selected. Either is a candidate for re-building the select
        if ($bloodlustPerPullSelect.val() === null || $bloodlustPerPullSelect.val().length === 0) {
            // Remove existing options
            $bloodlustPerPullSelect.find('option').remove();
            let selectedPulls = [];

            let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            let sortedKillZones = _.sortBy(_.values(killZoneMapObjectGroup.objects), 'index');
            for (let i = 0; i < sortedKillZones.length; i++) {
                let killZone = sortedKillZones[i];

                let $option = jQuery('<option>', {
                    value: killZone.id,
                    text: lang.get(`messages.simulate_pull`, {'index': killZone.index})
                });

                $bloodlustPerPullSelect.append($option);
            }

            let bloodlustKeys = [MAP_ICON_TYPE_SPELL_BLOODLUST, MAP_ICON_TYPE_SPELL_HEROISM];
            let mapIconMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);
            for (let index in mapIconMapObjectGroup.objects) {
                let mapIcon = mapIconMapObjectGroup.objects[index];

                // If we are a bloodlust icon...
                if (bloodlustKeys.includes(mapIcon.map_icon_type.key)) {
                    // Find the closest killzone
                    let closestKillZone = null;
                    let closestKillZoneDistance = 9999999;
                    for (let i = 0; i < sortedKillZones.length; i++) {
                        let killZone = sortedKillZones[i];
                        // Killzone not on the same floor as the icon - ignore
                        if (!killZone.getFloorIds().includes(mapIcon.floor_id)) {
                            continue;
                        }

                        let distance = getLatLngDistance(killZone.getLayerCenteroid(), mapIcon.layer.getLatLng());
                        if (closestKillZoneDistance > distance) {
                            closestKillZone = killZone;
                            closestKillZoneDistance = distance;
                        }
                    }

                    if (closestKillZone !== null) {
                        selectedPulls.push(closestKillZone.id);
                    }
                }
            }

            $bloodlustPerPullSelect.val(selectedPulls);
        }
    }

    /**
     *
     * @private
     */
    _saveSettings() {
        Cookies.set('simulate_modal_settings', JSON.stringify(this._getData()), cookieDefaultAttributes);
    }

    /**
     *
     * @private
     */
    _simulateModalOpened() {
        if (this._initialized) {
            return;
        }

        let self = this;

        let $keyLevelSlider = $('#simulate_key_level')
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 2,
                max: 40
            });

        let $hpPercentage = $('#simulate_hp_percent')
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 0,
                max: 100
            });

        let $rangedPullCompensationYards = $('#simulate_ranged_pull_compensation_yards')
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 0,
                max: 50
            });

        this._initialized = true;
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
            data: $.extend({
                // This value is different per route - we don't want to save it to cookies nor have it be restored from cookies
                simulate_bloodlust_per_pull: $('#simulate_bloodlust_per_pull').val()
            }, this._getData()),
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
