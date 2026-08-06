/**
 * @typedef {Object} DungeonrouteEditOptions
 * @property {Object} dungeonroute
 * @property {number} levelMin
 * @property {number} levelMax
 * @property {boolean} [noUI]
 */

/**
 * @property {DungeonrouteEditOptions} options
 */
class DungeonrouteEdit extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);


        this.settingsTabRoute = new SettingsTabRoute(options);
    }

    activate() {
        super.activate();

        if (!this.options.noUI) {
            this.settingsTabRoute.activate();
        }
    }
}
