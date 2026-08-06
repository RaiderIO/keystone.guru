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
            'unpublished': 'fa-plane-arrival',
            'team': 'fa-users',
            'world': 'fa-globe',
            'world_with_link': 'fa-link',
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
                    subtext: this.addNewlines(lang.get(`js.publish_state_subtext_${publishState}`), 60),
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
        let killZoneMapObjectGroup = this._getKillZoneMapObjectGroup();
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
        let killZoneMapObjectGroup = this._getKillZoneMapObjectGroup();
        if (killZoneMapObjectGroup !== null) {
            killZoneMapObjectGroup.unregister('killzone:enemyadded', this);
            killZoneMapObjectGroup.unregister('killzone:enemyremoved', this);
            killZoneMapObjectGroup.unregister('killzone:enemieschanged', this);
        }

        super.cleanup();
    }

    /**
     * The kill zone map object group of the currently displayed map, or null if this page has no editable map.
     * @returns {KillZoneMapObjectGroup|null}
     * @private
     */
    _getKillZoneMapObjectGroup() {
        // The share modal this select lives in is also rendered on pages that have no map. Both fallbacks on that
        // path yield `false` rather than a nullish value - util.js defines a no-op getState() and
        // MapObjectGroupManager.getByName() returns false for an absent group - so normalise with ||, not ??.
        let state = typeof getState === 'function' ? getState() : null;
        let dungeonMap = state ? state.getDungeonMap() : null;

        return (dungeonMap?.mapObjectGroupManager?.getByName(MAP_OBJECT_GROUP_KILLZONE)) || null;
    }

    /**
     * A route that does not kill all required enemies may not be published - but it must always remain possible to
     * _unpublish_ it, so only the publishing options are disabled rather than the select as a whole.
     * @private
     */
    _refreshRequiredEnemiesState() {
        let hasKilledAllRequiredEnemies = this._getKillZoneMapObjectGroup().hasKilledAllRequiredEnemies();
        let $select = $(this.options.publishSelector);

        $select.find('option').each((index, option) => {
            if (option.value === 'unpublished') {
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

    /**
     * @param {string} str
     * @param {number} [count=30]
     * @returns {string}
     */
    addNewlines(str, count = 30) {
        let result = '';
        let charsAdded = 0;
        let words = str.split(' ');

        for (let i = 0; i < words.length; i++) {
            let word = words[i];
            if (charsAdded + word.length > count) {
                result += '<br>';
                charsAdded = 0;
            }

            result += word + ' ';

            // Add one for the space added
            charsAdded += (word.length + 1);
        }

        return result;
    }
}
