// ---------------------------------------------------------------------------
// `addtocollectionrow.js` is concatenated into a bundle in the browser and
// resolves its translations through the bare global `lang`.
// ---------------------------------------------------------------------------

const Lang = require('lang.js');

const {AddToCollectionRow} = require('./addtocollectionrow');

const MESSAGES = {
    'en.js':           {
        add_to_collection_count:                ':count / :max',
        add_to_collection_kind_season_set:      ':season set · :covered/:total dungeons',
        add_to_collection_kind_free_form_none:  ':game_version · no dungeons',
        add_to_collection_kind_free_form_one:   ':game_version · :count dungeon',
        add_to_collection_kind_free_form_many:  ':game_version · :count dungeons',
        add_to_collection_blocked_game_version: 'Only :game_version routes',
        add_to_collection_blocked_season:       'Only :season routes',
        add_to_collection_blocked_full:         'Full',
        add_to_collection_blocked_dungeon_full: 'Full for this dungeon',
    },
    'en.gameversions': {retail: 'Retail', classic: 'Classic Era'},
};

/**
 * @param {Object} overrides
 * @returns {AddToCollectionRow}
 */
function row(overrides = {}) {
    return new AddToCollectionRow(Object.assign({
        public_key:             'colA',
        name:                   'Season set',
        game_version:           'gameversions.retail',
        season:                 {name: 'Season 3', name_long: 'Midnight Season 3', dungeon_count: 8},
        covered_dungeon_count:  1,
        route_count:            3,
        max_routes:             24,
        same_dungeon_route_count: 1,
        max_routes_per_dungeon: 2,
        contains_dungeon_route: false,
        blocked_reason:         null,
        store_url:              '/store/colA',
        delete_url:             '/delete/colA',
    }, overrides));
}

describe('AddToCollectionRow', () => {
    let previousLang;

    beforeEach(() => {
        previousLang = globalThis.lang;
        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});
    });

    afterEach(() => {
        globalThis.lang = previousLang;
    });

    it('getBlockedText_givenAnotherGameVersion_namesTheGameVersion', () => {
        // Arrange
        let collection = row({game_version: 'gameversions.classic', blocked_reason: 'game_version'});

        // Act
        let text = collection.getBlockedText();

        // Assert
        expect(text).toBe('Only Classic Era routes');
    });

    it('getBlockedText_givenAnotherSeason_namesTheSeason', () => {
        // Arrange
        let collection = row({blocked_reason: 'season'});

        // Act
        let text = collection.getBlockedText();

        // Assert
        expect(text).toBe('Only Midnight Season 3 routes');
    });

    it('getBlockedText_givenAFullDungeon_saysTheDungeonIsFull', () => {
        // Arrange
        let collection = row({blocked_reason: 'dungeon_full', same_dungeon_route_count: 2});

        // Act
        let text = collection.getBlockedText();

        // Assert
        expect(text).toBe('Full for this dungeon');
        expect(collection.isBlocked()).toBe(true);
    });

    it('getBlockedText_givenARouteThatMayJoin_returnsNull', () => {
        // Arrange
        let collection = row();

        // Act
        let text = collection.getBlockedText();

        // Assert
        expect(text).toBeNull();
        expect(collection.isBlocked()).toBe(false);
    });

    it('getBlockedReason_givenACollectionThatHoldsTheRoute_returnsNull', () => {
        // Arrange
        let collection = row({contains_dungeon_route: true, route_count: 24});

        // Act
        let reason = collection.getBlockedReason();

        // Assert
        expect(reason).toBeNull();
    });

    it('getBlockedReason_givenTheRouteWasRemovedFromAFullCollection_returnsNullAgain', () => {
        // Arrange
        let collection = row({contains_dungeon_route: true, route_count: 24});

        // Act
        collection.setContainsDungeonRoute(false);

        // Assert
        expect(collection.dungeonRouteCount).toBe(23);
        expect(collection.getBlockedReason()).toBeNull();
    });

    it('getBlockedReason_givenTheRouteWasRemovedFromItsFullDungeon_returnsNullAgain', () => {
        // Arrange
        let collection = row({contains_dungeon_route: true, same_dungeon_route_count: 2});

        // Act
        collection.setContainsDungeonRoute(false);

        // Assert
        expect(collection.sameDungeonRouteCount).toBe(1);
        expect(collection.getBlockedReason()).toBeNull();
    });

    it('getBlockedReason_givenADungeonStillPastItsLimitAfterTheRouteWasRemoved_returnsDungeonFull', () => {
        // Arrange - the collection held three routes of the dungeon from before the limit
        let collection = row({contains_dungeon_route: true, same_dungeon_route_count: 3});

        // Act
        collection.setContainsDungeonRoute(false);

        // Assert
        expect(collection.getBlockedReason()).toBe('dungeon_full');
    });

    it('getBlockedReason_givenTheLastFreeSlotWasTakenByAnotherRoute_returnsFull', () => {
        // Arrange
        let collection = row({route_count: 24, blocked_reason: 'full'});

        // Act
        let reason = collection.getBlockedReason();

        // Assert
        expect(reason).toBe('full');
        expect(collection.getBlockedText()).toBe('Full');
    });

    it('getBlockedReason_givenAnotherSeasonAndAFullCollection_keepsTheServersReason', () => {
        // Arrange
        let collection = row({route_count: 24, blocked_reason: 'season'});

        // Act
        let reason = collection.getBlockedReason();

        // Assert
        expect(reason).toBe('season');
    });

    it.each([
        [0, 'Retail · no dungeons'],
        [1, 'Retail · 1 dungeon'],
        [4, 'Retail · 4 dungeons'],
    ])('getKindText_givenAFreeFormCollectionCovering%iDungeons_says(%s)', (covered, expected) => {
        // Arrange
        let collection = row({season: null, covered_dungeon_count: covered});

        // Act
        let text = collection.getKindText();

        // Assert
        expect(text).toBe(expected);
    });

    it('getKindText_givenASeasonSet_saysHowManyDungeonsOfTheSeasonItCovers', () => {
        // Arrange
        let collection = row();

        // Act
        let text = collection.getKindText();

        // Assert
        expect(text).toBe('Season 3 set · 1/8 dungeons');
    });

    it('toTemplateData_givenASaveInProgress_disablesTheInputOnly', () => {
        // Arrange
        let collection = row();
        collection.isSaving = true;

        // Act
        let data = collection.toTemplateData();

        // Assert
        expect(data.is_input_disabled).toBe(true);
        expect(data.is_disabled).toBe(false);
        expect(data.count_text).toBe('3 / 24');
    });
});
