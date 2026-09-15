// ---------------------------------------------------------------------------
// Regression suite for #4456: the week filter used to turn a week index into a URL period by adding it to the
// season's start period. That number is passed through to Raider.IO verbatim as a keystone leaderboard period,
// and the addition is wrong whenever the season's raw start date already falls on or after the region's reset -
// Midnight season 2 (start_period 1076, week 1 also period 1076) then asked for week 2's data under week 1's
// label. The filter now looks both directions up in the list SeasonService::getSeasonWeeks() computed.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {SearchFilter} = require('./filter');
globalThis.SearchFilter = SearchFilter;

const {SearchFilterInput} = require('./filterinput');
globalThis.SearchFilterInput = SearchFilterInput;

const {SearchFilterWeeklyAffixGroups} = require('./filterinputweeklyaffixgroups');

// Midnight season 2 as seeded: raw start 2026-08-12, start_period 1076, week 1 falling in period 1076 as well.
// The old `seasonStartPeriod + week` would have produced 1077 for week 1.
const SEASON_WEEKS = [
    {week: 1, period: 1076},
    {week: 2, period: 1077},
    {week: 3, period: 1078},
    {week: 4, period: 1079},
];

describe('SearchFilterWeeklyAffixGroups', () => {
    let filter;

    beforeEach(() => {
        globalThis.getState = () => ({
            getMapContext: () => ({
                getSeasonWeeks: () => SEASON_WEEKS,
            }),
        });

        filter = new SearchFilterWeeklyAffixGroups(null, () => {
        }, {passThrough: true});
    });

    test('getParamsOverride_givenSelectedWeeks_returnsThePeriodsThoseWeeksFallIn', () => {
        // Arrange
        filter.setValue([1, 2, 3]);

        // Act
        const result = filter.getParamsOverride();

        // Assert
        expect(result).toEqual({minPeriod: 1076, maxPeriod: 1078});
    });

    test('getParamsOverride_givenNoSelection_returnsZeroForBothBounds', () => {
        // Arrange
        filter.setValue([]);

        // Act
        const result = filter.getParamsOverride();

        // Assert
        expect(result).toEqual({minPeriod: 0, maxPeriod: 0});
    });

    test('getParamsOverride_givenWeekOutsideTheSeason_returnsZeroForThatBound', () => {
        // Arrange
        filter.setValue([1, 2, 3, 4, 5]);

        // Act
        const result = filter.getParamsOverride();

        // Assert
        expect(result).toEqual({minPeriod: 1076, maxPeriod: 0});
    });

    test('setValueOverride_givenPeriodRange_selectsEveryWeekFallingInIt', () => {
        // Arrange
        filter.setValue([]);

        // Act
        filter.setValueOverride('minPeriod', 1077);
        filter.setValueOverride('maxPeriod', 1079);

        // Assert
        expect(filter.getValue()).toEqual([2, 3, 4]);
    });

    test('setValueOverride_givenPeriodZero_selectsNothing', () => {
        // Arrange
        filter.setValue([1, 2, 3]);

        // Act
        filter.setValueOverride('minPeriod', 0);

        // Assert
        expect(filter.getValue()).toEqual([]);
    });

    test('setValueOverride_givenPeriodNoWeekOfTheSeasonFallsIn_selectsNothing', () => {
        // Arrange
        filter.setValue([1, 2, 3]);

        // Act
        filter.setValueOverride('minPeriod', 1500);

        // Assert
        expect(filter.getValue()).toEqual([]);
    });

    test('setValueOverride_givenAPeriodRangeFromAnExistingLink_roundTripsBackToTheSamePeriods', () => {
        // Arrange
        filter.setValue([]);

        // Act
        filter.setValueOverride('minPeriod', 1077);
        filter.setValueOverride('maxPeriod', 1078);

        // Assert
        expect(filter.getParamsOverride()).toEqual({minPeriod: 1077, maxPeriod: 1078});
    });
});
