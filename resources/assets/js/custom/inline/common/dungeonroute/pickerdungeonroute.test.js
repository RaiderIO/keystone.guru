// ---------------------------------------------------------------------------
// `pickerdungeonroute.js` is concatenated into a bundle in the browser and
// resolves its translations through the bare global `lang`.
// ---------------------------------------------------------------------------

const Lang = require('lang.js');

const {PickerDungeonRoute} = require('./pickerdungeonroute');
const {isColorDark} = require('../../../colorutil');

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
        globalThis.isColorDark = isColorDark;
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

    it('getTags_givenPersonalTags_returnsThemWithTheirDarkness', () => {
        // Arrange
        const route = dungeonRoute({tagspersonal: [{name: 'WIP', color: '#000000'}, {name: 'Guild', color: null}]});

        // Act
        const tags = route.getTags();

        // Assert
        expect(tags).toEqual([
            {name: 'WIP', color: '#000000', dark: true},
            {name: 'Guild', color: null, dark: false},
        ]);
    });

    it('getTags_givenOnlyTeamTags_returnsTheTeamTags', () => {
        expect(dungeonRoute({tagsteam: [{name: 'Team', color: '#ffffff'}]}).getTags()).toEqual([
            {name: 'Team', color: '#ffffff', dark: false},
        ]);
    });

    it('getTags_givenNoTagRelation_returnsAnEmptyArray', () => {
        expect(dungeonRoute().getTags()).toEqual([]);
    });

    it('toTemplateData_givenTagsHidden_carriesNoTags', () => {
        const route = dungeonRoute({tagspersonal: [{name: 'WIP', color: null}]});

        expect(route.toTemplateData('https://assets/images').tags).toEqual([]);
        expect(route.toTemplateData('https://assets/images', true).tags).toHaveLength(1);
    });

    it('getTags_givenPersonalAndTeamTags_prefersThePersonalTags', () => {
        // Arrange
        const route = dungeonRoute({tagspersonal: [{name: 'Mine', color: null}], tagsteam: [{name: 'Team', color: null}]});

        // Act
        const tags = route.getTags();

        // Assert
        expect(tags.map((tag) => tag.name)).toEqual(['Mine']);
    });

    it('getKeyRangeText_givenARangeOfKeyLevels_returnsTheRange', () => {
        expect(dungeonRoute({level_min: 2, level_max: 10}).getKeyRangeText()).toBe('+2 - +10');
    });

    it('getKeyRangeText_givenOnlyAMinimumKeyLevel_returnsThatLevel', () => {
        expect(dungeonRoute({level_min: 7, level_max: null}).getKeyRangeText()).toBe('+7');
    });

    it('getEnemyForcesRequired_givenANonTeemingRoute_returnsTheRegularRequirement', () => {
        expect(dungeonRoute({teeming: 0}).getEnemyForcesRequired()).toBe(300);
        expect(dungeonRoute({teeming: 0}).hasEnoughEnemyForces()).toBe(true);
    });

    it('getEnemyForces_givenNoUsableCount_returnsZero', () => {
        expect(dungeonRoute({enemy_forces: null}).getEnemyForces()).toBe(0);
        expect(dungeonRoute({enemy_forces: 'abc'}).getEnemyForces()).toBe(0);
    });

    it('getRating_givenAPerfectRating_returnsFiveFullStars', () => {
        expect(dungeonRoute({rating: 10}).getRating().stars).toEqual(Array(5).fill('fas fa-star'));
    });

    it('getRating_givenAnEvenRating_endsOnEmptyStarsWithoutAHalfStar', () => {
        expect(dungeonRoute({rating: 8}).getRating().stars).toEqual([
            'fas fa-star', 'fas fa-star', 'fas fa-star', 'fas fa-star', 'far fa-star',
        ]);
    });

    it('getRating_givenAFractionalRating_roundsItToTheNearestHalfStar', () => {
        expect(dungeonRoute({rating: '6.6'}).getRating().stars).toEqual([
            'fas fa-star', 'fas fa-star', 'fas fa-star', 'fas fa-star-half-alt', 'far fa-star',
        ]);
    });

    it('getRating_givenVotesButNoUsableRating_returnsFiveEmptyStars', () => {
        expect(dungeonRoute({rating: null, rating_count: 2}).getRating()).toEqual({
            stars: Array(5).fill('far fa-star'),
            title: '2 votes',
        });
    });

    it('getPullGraph_givenTrashAndBossPulls_scalesTrashToTheBiggestPullAndDrawsBossesFullHeight', () => {
        // Arrange
        const pullForces = [
            {enemy_forces: 40, has_boss: false},
            {enemy_forces: 20, has_boss: false},
            {enemy_forces: 1, has_boss: false},
            {enemy_forces: 0, has_boss: true},
            {enemy_forces: 0, has_boss: false},
        ];

        // Act
        const pullGraph = dungeonRoute({pull_forces: pullForces}).getPullGraph();

        // Assert
        expect(pullGraph).toEqual({
            width:  15,
            height: 22,
            title:  '5 pulls',
            bars:   [
                {x: 0, y: 0, width: 3, height: 22, fill: 'currentColor'},
                {x: 4, y: 11, width: 3, height: 11, fill: 'currentColor'},
                {x: 8, y: 20, width: 3, height: 2, fill: 'currentColor'},
                {x: 12, y: 0, width: 3, height: 22, fill: 'rgba(240, 180, 60, 0.9)'},
            ],
        });
    });

    it('getPullGraph_givenASingleBossPull_saysOnePullAndDrawsItFullHeight', () => {
        // Act
        const pullGraph = dungeonRoute({pull_forces: [{enemy_forces: 0, has_boss: true}]}).getPullGraph();

        // Assert
        expect(pullGraph.title).toBe('1 pull');
        expect(pullGraph.bars).toEqual([{x: 0, y: 0, width: 3, height: 22, fill: 'rgba(240, 180, 60, 0.9)'}]);
    });

    it('getPullGraph_givenEmptyPullsBeforeTheCap_dropsThemBeforeCapping', () => {
        // Arrange
        const pullForces = Array.from({length: 30}, () => ({enemy_forces: 0, has_boss: false}))
            .concat([{enemy_forces: 10, has_boss: false}]);

        // Act
        const pullGraph = dungeonRoute({pull_forces: pullForces}).getPullGraph();

        // Assert
        expect(pullGraph).not.toBeNull();
        expect(pullGraph.bars).toHaveLength(1);
        expect(pullGraph.title).toBe('31 pulls');
    });

    it('getThumbnailUrl_givenAThumbnail_returnsItsUrl', () => {
        // Arrange
        const route = dungeonRoute({has_thumbnail: true, thumbnails: [{url: 'https://thumb/1.jpg'}, {url: 'https://thumb/2.jpg'}]});

        // Act + Assert
        expect(route.getThumbnailUrl('https://assets/images')).toBe('https://thumb/1.jpg');
    });

    it('getThumbnailUrl_givenAThumbnailFlagWithoutThumbnails_returnsTheDungeonImage', () => {
        expect(dungeonRoute({has_thumbnail: true, thumbnails: []}).getThumbnailUrl('https://assets/images'))
            .toBe('https://assets/images/dungeons/tww/arakara_3-2.jpg');
    });

    it('toTemplateData_givenARouteWithEnoughEnemyForces_carriesNoWarning', () => {
        expect(dungeonRoute({enemy_forces: 300}).toTemplateData('https://assets/images').enemy_forces_warning).toBe('');
    });

    it('toTemplateData_givenARoute_carriesItsIdentityAndRowDetails', () => {
        // Act
        const data = dungeonRoute().toTemplateData('https://assets/images');

        // Assert
        expect(data).toMatchObject({
            public_key:     'abc',
            title:          'Route',
            dungeon_name:   'Ara-Kara',
            is_unpublished: false,
            key_range:      '+2 - +10',
            views:          '1.5K',
            rating:         {title: '4 votes'},
            pull_graph:     null,
        });
    });
});
