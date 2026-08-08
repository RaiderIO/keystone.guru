/**
 * @typedef {Object} CommonDungeonroutePublishOptions
 * @property {string} publishSelector
 * @property {string[]} publishStates
 * @property {string[]} publishStatesAvailable
 * @property {string} publishStateSelected
 */

/**
 * @property {CommonDungeonroutePublishOptions} options
 */
class CommonDungeonroutePublish extends InlineCode {
    activate() {
        super.activate();

        let self = this;

        let $select = $(this.options.publishSelector);

        let icons = {
            [PUBLISHED_STATE_UNPUBLISHED]: 'fa-plane-arrival',
            [PUBLISHED_STATE_TEAM]: 'fa-users',
            [PUBLISHED_STATE_WORLD]: 'fa-globe',
            [PUBLISHED_STATE_WORLD_WITH_LINK]: 'fa-link',
        }

        for (let index in this.options.publishStates) {
            if (this.options.publishStates.hasOwnProperty(index)) {
                let publishState = this.options.publishStates[index];

                let template = Handlebars.templates['select_option_icon_subtext_template'];

                let optionData = {
                    value: publishState,
                    text: lang.get(`js.publish_state_title_${publishState}`),
                    selected: this.options.publishStateSelected === publishState ? 'selected' : false,
                };

                // If the user cannot activate an option for some reason, disable it but keep it visible
                if (!this.options.publishStatesAvailable.includes(publishState)) {
                    optionData.disabled = true;
                }
                let option = jQuery('<option>', optionData);


                let data = {
                    title: lang.get(`js.publish_state_title_${publishState}`),
                    subtext: lang.get(`js.publish_state_subtext_${publishState}`),
                    fa_class: icons[publishState]
                };

                $select.append(
                    $(option).attr('data-content', template(data))
                );
            }
        }

        // When changed, trigger the change in the backend too
        $select.bind('change', function () {
            self._setPublished($(this).val());
        });

        // Only the route editor has a map (and thus kill zones) to check the required enemies against
        let killZoneMapObjectGroup = getKillZoneMapObjectGroup();
        if (killZoneMapObjectGroup !== null) {
            this._refreshRequiredEnemiesState();
            // KillZone.setEnemies() suppresses the per-enemy signals and emits only enemieschanged - that is the
            // path remote/live-session updates take, so all three are needed to keep the state fresh
            killZoneMapObjectGroup.register('killzone:enemyadded', this, this._refreshRequiredEnemiesState.bind(this));
            killZoneMapObjectGroup.register('killzone:enemyremoved', this, this._refreshRequiredEnemiesState.bind(this));
            killZoneMapObjectGroup.register('killzone:enemieschanged', this, this._refreshRequiredEnemiesState.bind(this));
        }
    }

    cleanup() {
        let killZoneMapObjectGroup = getKillZoneMapObjectGroup();
        if (killZoneMapObjectGroup !== null) {
            killZoneMapObjectGroup.unregister('killzone:enemyadded', this);
            killZoneMapObjectGroup.unregister('killzone:enemyremoved', this);
            killZoneMapObjectGroup.unregister('killzone:enemieschanged', this);
        }

        super.cleanup();
    }

    /**
     * A route that does not kill all required enemies may not be published - but it must always remain possible to
     * _unpublish_ it, so only the publishing options are disabled rather than the select as a whole.
     * @private
     */
    _refreshRequiredEnemiesState() {
        let hasKilledAllRequiredEnemies = getKillZoneMapObjectGroup().hasKilledAllRequiredEnemies();
        let $select = $(this.options.publishSelector);

        $select.find('option').each((index, option) => {
            if (option.value === PUBLISHED_STATE_UNPUBLISHED) {
                return;
            }

            // Never re-enable an option that was unavailable for another reason to begin with
            let isAvailable = this.options.publishStatesAvailable.includes(option.value);
            option.disabled = !isAvailable || !hasKilledAllRequiredEnemies;
        });

        refreshSelectPickers();

        $('#map_route_publish_container')
            .attr('data-toggle', 'tooltip')
            .attr('title', hasKilledAllRequiredEnemies ? '' : lang.get('js.cannot_change_sharing_settings_not_all_required_enemies_killed'))
            .refreshTooltips();
    }

    /**
     * @param {string} value Must be one of the available published states
     * @private
     */
    _setPublished(value) {
        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/publishedState`,
            dataType: 'json',
            data: {
                published_state: value
            },
            success: function (json) {
                showSuccessNotification(lang.get('js.route_published_state_changed'));
            }
        });
    }
}
