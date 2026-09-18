/**
 * @typedef {Object} CommonDungeonroutePublishOptions
 * @property {string} publishSelector The server-rendered common.forms.publishedstate select.
 * @property {string[]} publishStatesAvailable
 */

/**
 * @property {CommonDungeonroutePublishOptions} options
 */
class CommonDungeonroutePublish extends InlineCode {
    activate() {
        super.activate();

        let self = this;

        let $select = $(this.options.publishSelector);

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
