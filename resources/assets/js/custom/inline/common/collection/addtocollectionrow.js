/**
 * A collection as the "Add to collection…" dialog lists it, for one dungeon route: what the collection covers, whether
 * the route is in it and, when it is not, whether it may be added. Everything shown is worded here from the data the
 * collections endpoint returns.
 */
class AddToCollectionRow {

    static BLOCKED_GAME_VERSION = 'game_version';
    static BLOCKED_SEASON = 'season';
    static BLOCKED_FULL = 'full';
    static BLOCKED_DUNGEON_FULL = 'dungeon_full';

    /**
     * @param {Object} json One collection of the collections endpoint's response.
     */
    constructor(json) {
        this.json = json;

        /** @type {string} */
        this.publicKey = json.public_key;
        /** @type {string} */
        this.name = json.name;
        /** @type {Number} */
        this.dungeonRouteCount = json.route_count;
        /** @type {Number} */
        this.maxDungeonRoutes = json.max_routes;
        /** @type {Number} How many routes of the route's own dungeon the collection holds */
        this.sameDungeonRouteCount = json.same_dungeon_route_count;
        /** @type {Number} */
        this.maxDungeonRoutesPerDungeon = json.max_routes_per_dungeon;
        /** @type {boolean} */
        this.containsDungeonRoute = json.contains_dungeon_route;
        /** @type {string} */
        this.storeUrl = json.store_url;
        /** @type {string} */
        this.deleteUrl = json.delete_url;
        /** @type {boolean} Set while a change to this collection is being saved. */
        this.isSaving = false;
    }

    /**
     * @param {boolean} containsDungeonRoute
     */
    setContainsDungeonRoute(containsDungeonRoute) {
        this.containsDungeonRoute = containsDungeonRoute;
        this.dungeonRouteCount += containsDungeonRoute ? 1 : -1;
        this.sameDungeonRouteCount += containsDungeonRoute ? 1 : -1;
    }

    /**
     * Why the route cannot be added to this collection; null when it is in it already or may be added.
     *
     * @returns {string|null} One of the BLOCKED_* constants.
     */
    getBlockedReason() {
        if (this.containsDungeonRoute) {
            return null;
        }

        // Fullness follows the route counts, which change as routes are added and removed
        let isFullnessReason = [AddToCollectionRow.BLOCKED_FULL, AddToCollectionRow.BLOCKED_DUNGEON_FULL].includes(this.json.blocked_reason);
        if (this.json.blocked_reason !== null && !isFullnessReason) {
            return this.json.blocked_reason;
        }

        if (this.dungeonRouteCount >= this.maxDungeonRoutes) {
            return AddToCollectionRow.BLOCKED_FULL;
        }

        return this.sameDungeonRouteCount >= this.maxDungeonRoutesPerDungeon ? AddToCollectionRow.BLOCKED_DUNGEON_FULL : null;
    }

    /**
     * @returns {boolean}
     */
    isBlocked() {
        return this.getBlockedReason() !== null;
    }

    /**
     * @returns {string|null}
     */
    getBlockedText() {
        switch (this.getBlockedReason()) {
            case AddToCollectionRow.BLOCKED_GAME_VERSION:
                return lang.get('js.add_to_collection_blocked_game_version', {game_version: lang.get(this.json.game_version)});
            case AddToCollectionRow.BLOCKED_SEASON:
                return lang.get('js.add_to_collection_blocked_season', {season: this.json.season?.name_long ?? ''});
            case AddToCollectionRow.BLOCKED_FULL:
                return lang.get('js.add_to_collection_blocked_full');
            case AddToCollectionRow.BLOCKED_DUNGEON_FULL:
                return lang.get('js.add_to_collection_blocked_dungeon_full');
            default:
                return null;
        }
    }

    /**
     * What the collection covers: the dungeons of its season, or the dungeons its routes are in.
     *
     * @returns {string}
     */
    getKindText() {
        let covered = this.json.covered_dungeon_count;

        if (this.json.season) {
            return lang.get('js.add_to_collection_kind_season_set', {
                season: this.json.season.name,
                covered: covered,
                total: this.json.season.dungeon_count,
            });
        }

        let key = covered === 0 ? 'none' : (covered === 1 ? 'one' : 'many');

        return lang.get(`js.add_to_collection_kind_free_form_${key}`, {
            game_version: lang.get(this.json.game_version),
            count: covered,
        });
    }

    /**
     * @returns {string}
     */
    getCountText() {
        return lang.get('js.add_to_collection_count', {count: this.dungeonRouteCount, max: this.maxDungeonRoutes});
    }

    /**
     * @returns {Object} The variables of the add_to_collection_row template.
     */
    toTemplateData() {
        let isBlocked = this.isBlocked();

        return {
            public_key: this.publicKey,
            name: this.name,
            kind_text: this.getKindText(),
            count_text: this.getCountText(),
            contains_dungeon_route: this.containsDungeonRoute,
            is_disabled: isBlocked,
            is_input_disabled: isBlocked || this.isSaving,
            blocked_text: this.getBlockedText(),
        };
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {AddToCollectionRow};
}
