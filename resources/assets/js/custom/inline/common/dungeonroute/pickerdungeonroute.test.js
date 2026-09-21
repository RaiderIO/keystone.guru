// ---------------------------------------------------------------------------
// `pickerdungeonroute.js` is concatenated into a bundle in the browser and
// resolves its translations through the bare global `lang`.
// ---------------------------------------------------------------------------

const Lang = require('lang.js');

const {PickerDungeonRoute} = require('./pickerdungeonroute');

const MESSAGES = {
    'en.js':       {
        dungeonroute_picker_key_level:    '+:level',
        dungeonroute_picker_key_range:    '+:min - +:max',
        dungeonroute_picker_enemy_forces: ':count/:required',
        dungeonroute_picker_pulls_one:    '1 pull',
        dungeonroute_picker_pulls_many:   ':count pulls',
        dungeonroute_picker_views:        ':count views',
        dungeonroute_picker_votes:        ':count votes',
    },
    'en.dungeons': {ara_kara: 'Ara-Kara'},
};

/**
 * @param {Object} overrides
 * @returns {PickerDungeonRoute}
 */
function dungeonRoute(overrides = {}) {
    return new PickerDungeonRoute(Object.assign({
        public_key:                    'abc',
        title:                         'Route',
        published:                     'world',
        level_min:                     2,
        level_max:                     10,
        teeming:                       0,
        views:                         1500,
        rating:                        7,
        rating_count:                  4,
        enemy_forces:                  310,
        enemy_forces_required:         300,
        enemy_forces_required_teeming: 350,
        pull_forces:                   [],
        has_thumbnail:                 false,
        thumbnails:                    [],
        dungeon:                       {id: 3, name: 'dungeons.ara_kara', key: 'arakara', expansion: {shortname: 'tww'}},
    }, overrides));
}

describe('PickerDungeonRoute', () => {
    let previousLang;

    beforeEach(() => {
        previousLang    = globalThis.lang;
        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});
    });

    afterEach(() => {
        globalThis.lang = previousLang;
    });

    it('getDungeonName_givenATranslationKey_translatesIt', () => {
        expect(dungeonRoute().getDungeonName()).toBe('Ara-Kara');
    });

    it('getEnemyForcesRequired_givenATeemingRoute_returnsTheTeemingRequirement', () => {
        expect(dungeonRoute({teeming: 1}).getEnemyForcesRequired()).toBe(350);
        expect(dungeonRoute({teeming: 1}).hasEnoughEnemyForces()).toBe(false);
    });

    it('getKeyRangeText_givenNoKeyLevels_returnsNothing', () => {
        expect(dungeonRoute({level_min: null, level_max: null}).getKeyRangeText()).toBe('');
    });

    it('getKeyRangeText_givenOneKeyLevel_returnsThatLevel', () => {
        expect(dungeonRoute({level_min: 5, level_max: 5}).getKeyRangeText()).toBe('+5');
    });

    it('getRating_givenAnOddRating_endsOnAHalfStar', () => {
        expect(dungeonRoute().getRating()).toEqual({
            stars: ['fas fa-star', 'fas fa-star', 'fas fa-star', 'fas fa-star-half-alt', 'far fa-star'],
            title: '4 votes',
        });
    });

    it('getRating_givenNoVotes_returnsNull', () => {
        expect(dungeonRoute({rating_count: 0}).getRating()).toBeNull();
    });

    it('getPullGraph_givenMoreThanThirtyPulls_capsTheBarsButCountsEveryPull', () => {
        // Arrange
        const pullForces = Array.from({length: 40}, () => ({enemy_forces: 10, has_boss: false}));

        // Act
        const pullGraph = dungeonRoute({pull_forces: pullForces}).getPullGraph();

        // Assert
        expect(pullGraph.bars).toHaveLength(30);
        expect(pullGraph.title).toBe('40 pulls');
        expect(pullGraph.width).toBe(119);
    });

    it('getPullGraph_givenOnlyEmptyPulls_returnsNull', () => {
        expect(dungeonRoute({pull_forces: [{enemy_forces: 0, has_boss: false}]}).getPullGraph()).toBeNull();
    });

    it('getViewsAbbreviated_givenMillions_abbreviatesToOneDecimal', () => {
        expect(dungeonRoute({views: 2340000}).getViewsAbbreviated()).toBe('2.3M');
        expect(dungeonRoute({views: 999}).getViewsAbbreviated()).toBe('999');
    });

    it('toTemplateData_givenARouteShortOnEnemyForces_carriesTheWarning', () => {
        // Act
        const data = dungeonRoute({enemy_forces: 290, published: 'unpublished'}).toTemplateData('https://assets/images');

        // Assert
        expect(data.enemy_forces_warning).toBe('290/300');
        expect(data.is_unpublished).toBe(true);
        expect(data.thumbnail_url).toBe('https://assets/images/dungeons/tww/arakara_3-2.jpg');
        expect(data.views_title).toBe('1500 views');
    });
});
