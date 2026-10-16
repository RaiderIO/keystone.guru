// ---------------------------------------------------------------------------
// CommonDungeonrouteCoverageAffixgroup is a global-script style class: it extends the bare global
// `InlineCode` and reaches the route table's and the create route form's inline code through the
// global `_inlineManager`, stubbed here as a registry the tests fill in when they want a code to exist.
// On /profile/routes the route table's inline code is initialized after the coverage's, and the
// affixes' only once the lazy create route modal has loaded its form.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.refreshSelectPickers = vi.fn();

const {CommonDungeonrouteCoverageAffixgroup} = require('./affixgroup');

const DUNGEON_ID = 7;
const AFFIX_GROUP_ID = 42;

let inlineCodes;

/**
 * @returns {CommonDungeonrouteCoverageAffixgroup}
 */
function buildAndActivate() {
    document.body.innerHTML = `
        <div class="dungeonroute_coverage_filter_select">
            <button class="btn" data-dungeon-id="${DUNGEON_ID}" data-affix-group-id="${AFFIX_GROUP_ID}"></button>
        </div>
        <div class="dungeonroute_coverage_new_dungeon_route">
            <button class="btn" data-dungeon-id="${DUNGEON_ID}" data-affix-group-id="${AFFIX_GROUP_ID}"></button>
            <button class="btn" data-dungeon-id="${DUNGEON_ID + 1}" data-affix-group-id="${AFFIX_GROUP_ID + 1}"></button>
        </div>
        <div id="create_route_modal"></div>`;

    const code = new CommonDungeonrouteCoverageAffixgroup('coverage', 'common/dungeonroute/coverage/affixgroup', {
        coverageFilterButtonSelector: '.dungeonroute_coverage_filter_select .btn',
        coverageNewDungeonRouteButtonSelector: '.dungeonroute_coverage_new_dungeon_route .btn',
        createRouteModalSelector: '#create_route_modal',
        seasonIdSelector: '#dungeonroute_coverage_season_id',
    });
    code.activate();

    return code;
}

describe('CommonDungeonrouteCoverageAffixgroup', () => {
    beforeEach(() => {
        inlineCodes = {};
        globalThis._inlineManager = {getInlineCode: (bladePath) => inlineCodes[bladePath] ?? []};
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('coveredCellClicked_givenTheRouteTableInitializedAfterActivation_filtersTheRouteTable', () => {
        // Arrange
        buildAndActivate();
        const table = {overrideSelection: vi.fn()};
        inlineCodes['dungeonroute/table'] = table;

        // Act
        const click = () => $('.dungeonroute_coverage_filter_select .btn').trigger('click');

        // Assert
        expect(click).not.toThrow();
        expect(table.overrideSelection).toHaveBeenCalledWith(DUNGEON_ID, [AFFIX_GROUP_ID]);
        expect(refreshSelectPickers).toHaveBeenCalledTimes(1);
    });

    it('newRouteCellClicked_givenTheCreateRouteFormLoaded_preselectsTheDungeonAndAffixGroup', () => {
        // Arrange
        buildAndActivate();
        const affixes = {overrideSelection: vi.fn()};
        inlineCodes['common/group/affixes'] = affixes;

        // Act
        $('.dungeonroute_coverage_new_dungeon_route .btn').first().trigger('click');

        // Assert
        expect(affixes.overrideSelection).toHaveBeenCalledWith(DUNGEON_ID, [AFFIX_GROUP_ID]);
        expect(refreshSelectPickers).toHaveBeenCalledTimes(1);
    });

    it('newRouteCellClicked_givenTheCreateRouteFormNotLoadedYet_preselectsOnceTheModalHasLoadedIt', () => {
        // Arrange
        buildAndActivate();
        const affixes = {overrideSelection: vi.fn()};

        // Act
        $('.dungeonroute_coverage_new_dungeon_route .btn').first().trigger('click');
        inlineCodes['common/group/affixes'] = affixes;
        $('#create_route_modal').trigger('modallazy:loaded');

        // Assert
        expect(affixes.overrideSelection).toHaveBeenCalledWith(DUNGEON_ID, [AFFIX_GROUP_ID]);
        expect(refreshSelectPickers).toHaveBeenCalledTimes(1);
    });

    it('newRouteCellClicked_givenTwoClicksBeforeTheModalLoaded_preselectsOnlyTheLastClickedCell', () => {
        // Arrange
        buildAndActivate();
        const affixes = {overrideSelection: vi.fn()};

        // Act
        $('.dungeonroute_coverage_new_dungeon_route .btn').eq(0).trigger('click');
        $('.dungeonroute_coverage_new_dungeon_route .btn').eq(1).trigger('click');
        inlineCodes['common/group/affixes'] = affixes;
        $('#create_route_modal').trigger('modallazy:loaded');

        // Assert
        expect(affixes.overrideSelection).toHaveBeenCalledTimes(1);
        expect(affixes.overrideSelection).toHaveBeenCalledWith(DUNGEON_ID + 1, [AFFIX_GROUP_ID + 1]);
    });

    it('newRouteCellClicked_givenAnotherModalLoadedListener_leavesItInPlace', () => {
        // Arrange
        buildAndActivate();
        const otherListener = vi.fn();
        $('#create_route_modal').on('modallazy:loaded', otherListener);

        // Act
        $('.dungeonroute_coverage_new_dungeon_route .btn').eq(0).trigger('click');
        $('.dungeonroute_coverage_new_dungeon_route .btn').eq(1).trigger('click');
        $('#create_route_modal').trigger('modallazy:loaded');

        // Assert
        expect(otherListener).toHaveBeenCalledTimes(1);
    });
});
