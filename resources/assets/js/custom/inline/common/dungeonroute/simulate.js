/**
 * @typedef {Object} SimulateOptions
 * @property {boolean} isThundering
 * @property {String} keyLevelSelector
 * @property {Number} keyLevelMin
 * @property {Number} keyLevelMax
 */

/**
 * @property {SimulateOptions} options
 */
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
    }

    /**
     *
     * @returns {object}
     * @private
     */
    _getData() {
        let raidBuffs = $('#simulate_raid_buffs').val();
        let raidBuffsMask = 0;
        for (let i = 0; i < raidBuffs.length; i++) {
            raidBuffsMask += parseInt(raidBuffs[i]);
        }

        return {
            key_level: $(this.options.keyLevelSelector).val(),
            shrouded_bounty_type: $('#simulate_shrouded_bounty_type').val(),
            affix: $('#simulate_affix').val(),
            thundering_clear_seconds: $('#simulate_thundering_clear_seconds').val(),
            raid_buffs_mask: raidBuffsMask,
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
                // Hidden inputs should not be modified

                if (!$input.is(':hidden')) {
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
    }

    /**
     *
     * @private
     */
    _refreshBloodlustPerPullsPullList() {

        // If not filled yet, fill the bloodlust per pull selector
        let $bloodlustPerPullSelect = $('#simulate_bloodlust_per_pull');
        let previouslySelectedPulls = $bloodlustPerPullSelect.val();

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

            // Determine if this pull activated Bloodlust~ spells
            for (let index in killZone.spellIds) {
                let spellId = killZone.spellIds[index];

                if (BLOODLUST_SPELLS.includes(spellId)) {
                    selectedPulls.push(killZone.id);
                    break;
                }
            }
        }

        if (previouslySelectedPulls.length === 0) {
            $bloodlustPerPullSelect.val(selectedPulls);
        }

        refreshSelectPickers();
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
        this._refreshBloodlustPerPullsPullList();

        if (this._initialized) {
            return;
        }

        $(this.options.keyLevelSelector)
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: this.options.keyLevelMin,
                max: this.options.keyLevelMax
            });

        if (this.options.isThundering) {
            $('#simulate_thundering_clear_seconds')
                .ionRangeSlider({
                    grid: true,
                    grid_snap: true,
                    type: 'single',
                    min: 0,
                    max: 15
                });
        }

        $('#simulate_hp_percent')
            .ionRangeSlider({
                grid: true,
                grid_snap: true,
                type: 'single',
                min: 0,
                max: 100
            });

        $('#simulate_ranged_pull_compensation_yards')
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
