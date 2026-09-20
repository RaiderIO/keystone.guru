/**
 * A route as /ajax/routes lists it, and what the route picker's row shows of it.
 */
class PickerDungeonRoute {

    /**
     * @param {Object} json One row of the /ajax/routes response.
     */
    constructor(json) {
        this.json = json;

        /** @type {string} */
        this.publicKey = json.public_key;
        /** @type {string} */
        this.title = json.title;
        /** @type {Number} */
        this.dungeonId = json.dungeon.id;
    }

    /**
     * @returns {string}
     */
    getDungeonName() {
        return lang.get(this.json.dungeon.name);
    }

    /**
     * @returns {Number}
     */
    getEnemyForces() {
        return parseInt(this.json.enemy_forces) || 0;
    }

    /**
     * @returns {Number}
     */
    getEnemyForcesRequired() {
        return parseInt(this.json.teeming === 1 ? this.json.enemy_forces_required_teeming : this.json.enemy_forces_required) || 0;
    }

    /**
     * @returns {boolean}
     */
    hasEnoughEnemyForces() {
        return this.getEnemyForces() >= this.getEnemyForcesRequired();
    }

    /**
     * @returns {boolean}
     */
    isUnpublished() {
        return this.json.published === 'unpublished';
    }

    /**
     * @param {string} fallbackImageBaseUrl
     * @returns {string}
     */
    getThumbnailUrl(fallbackImageBaseUrl) {
        if (this.json.has_thumbnail && this.json.thumbnails && this.json.thumbnails.length > 0) {
            return this.json.thumbnails[0].url;
        }

        return `${fallbackImageBaseUrl}/dungeons/${this.json.dungeon.expansion.shortname}/${this.json.dungeon.key}_3-2.jpg`;
    }

    /**
     * @returns {string} Empty for a route without key levels.
     */
    getKeyRangeText() {
        let min = this.json.level_min;
        let max = this.json.level_max;

        if (min === null || typeof min === 'undefined') {
            return '';
        }

        if (max === null || typeof max === 'undefined' || min === max) {
            return lang.get('js.dungeonroute_picker_key_level', {level: min});
        }

        return lang.get('js.dungeonroute_picker_key_range', {min: min, max: max});
    }

    /**
     * Five stars, half and empty ones included - the stars of common.dungeonroute.rating.
     *
     * @returns {{stars: string[], title: string}|null} Null for a route nobody rated.
     */
    getRating() {
        let count = parseInt(this.json.rating_count) || 0;
        if (count === 0) {
            return null;
        }

        let rating = Math.round(parseFloat(this.json.rating) || 0);
        let stars = [];

        for (let star = 1; star <= 5; star++) {
            if (rating === (star * 2) - 1) {
                stars.push('fas fa-star-half-alt');
            } else if (rating >= star * 2) {
                stars.push('fas fa-star');
            } else {
                stars.push('far fa-star');
            }
        }

        return {stars: stars, title: lang.get('js.dungeonroute_picker_votes', {count: count})};
    }

    /**
     * The route's "fingerprint": one bar per pull, its height the pull's share of the biggest trash pull, bosses full
     * height in the accent colour. Mirrors common.dungeonroute.pullgraph, which draws the same graph on the route rows
     * of the site.
     *
     * @returns {{width: Number, height: Number, title: string, bars: Object[]}|null} Null without a pull worth drawing.
     */
    getPullGraph() {
        let barWidth = 3;
        let barGap = 1;
        let minBar = 2;
        let maxBars = 30;
        let chartHeight = 22;

        let pullForces = this.json.pull_forces || [];
        // A pull that grants no forces and holds no boss says nothing - drop it before the cap, so it
        // neither renders as noise nor eats into the bar budget
        let pulls = pullForces.filter(pull => pull.enemy_forces > 0 || pull.has_boss).slice(0, maxBars);

        if (pulls.length === 0) {
            return null;
        }

        let maxForces = Math.max(0, ...pulls.filter(pull => !pull.has_boss).map(pull => pull.enemy_forces));

        return {
            width: (pulls.length * (barWidth + barGap)) - barGap,
            height: chartHeight,
            title: pullForces.length === 1
                ? lang.get('js.dungeonroute_picker_pulls_one')
                : lang.get('js.dungeonroute_picker_pulls_many', {count: pullForces.length}),
            bars: pulls.map(function (pull, index) {
                let barHeight = pull.has_boss
                    ? chartHeight
                    : (maxForces > 0 ? Math.max(minBar, Math.round((pull.enemy_forces / maxForces) * chartHeight)) : minBar);

                return {
                    x: index * (barWidth + barGap),
                    y: chartHeight - barHeight,
                    width: barWidth,
                    height: barHeight,
                    fill: pull.has_boss ? 'rgba(240, 180, 60, 0.9)' : 'currentColor',
                };
            }),
        };
    }

    /**
     * Mirrors the abbreviateNumber() PHP helper the route rows render their counts with.
     * @returns {string}
     */
    getViewsAbbreviated() {
        let views = parseInt(this.json.views) || 0;
        let round = value => String(parseFloat(value.toFixed(1)));

        if (views >= 1000000) {
            return `${round(views / 1000000)}M`;
        }

        if (views >= 1000) {
            return `${round(views / 1000)}K`;
        }

        return String(views);
    }

    /**
     * @param {string} fallbackImageBaseUrl
     * @returns {Object} The variables of the dungeonroute_picker_row template.
     */
    toTemplateData(fallbackImageBaseUrl) {
        return {
            public_key: this.publicKey,
            title: this.title,
            dungeon_name: this.getDungeonName(),
            thumbnail_url: this.getThumbnailUrl(fallbackImageBaseUrl),
            is_unpublished: this.isUnpublished(),
            key_range: this.getKeyRangeText(),
            // A route that makes the required count need not say so, like the route rows on the site
            enemy_forces_warning: this.getEnemyForcesRequired() === 0 || this.hasEnoughEnemyForces() ? '' :
                lang.get('js.dungeonroute_picker_enemy_forces', {
                    count: this.getEnemyForces(),
                    required: this.getEnemyForcesRequired(),
                }),
            rating: this.getRating(),
            pull_graph: this.getPullGraph(),
            views: this.getViewsAbbreviated(),
            views_title: lang.get('js.dungeonroute_picker_views', {count: parseInt(this.json.views) || 0}),
        };
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {PickerDungeonRoute};
}
