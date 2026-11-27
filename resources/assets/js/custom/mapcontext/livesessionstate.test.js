const {LiveSessionState} = require('./livesessionstate.js');

describe('LiveSessionState', () => {
    describe('decodeEnemyIds', () => {
        test('decodeEnemyIds_givenDeltaEncodedIds_returnsTheIds', () => {
            // Act
            const enemyIds = LiveSessionState.decodeEnemyIds([1200, 3, 97]);

            // Assert
            expect(enemyIds).toEqual([1200, 1203, 1300]);
        });

        test('decodeEnemyIds_givenNothing_returnsAnEmptyList', () => {
            // Act
            const enemyIds = LiveSessionState.decodeEnemyIds(undefined);

            // Assert
            expect(enemyIds).toEqual([]);
        });
    });

    describe('constructor', () => {
        test('constructor_givenTheServersState_decodesEveryEnemyList', () => {
            // Act
            const liveSessionState = new LiveSessionState({
                killed: [10, 5],
                in_combat: [20],
                obsolete: [30, 1],
                overpulled: [[40, 7], [41, 8]],
                players: [],
            });

            // Assert
            expect([...liveSessionState.killedEnemyIds]).toEqual([10, 15]);
            expect([...liveSessionState.inCombatEnemyIds]).toEqual([20]);
            expect([...liveSessionState.obsoleteEnemyIds]).toEqual([30, 31]);
            expect(liveSessionState.getOverpulledKillZoneId(40)).toBe(7);
            expect(liveSessionState.getOverpulledKillZoneId(41)).toBe(8);
            expect(liveSessionState.getOverpulledKillZoneId(42)).toBeNull();
        });

        test('constructor_givenNoState_isEmpty', () => {
            // Act
            const liveSessionState = new LiveSessionState(null);

            // Assert
            expect(liveSessionState.killedEnemyIds.size).toBe(0);
            expect(liveSessionState.overpulledKillZoneIdByEnemyId.size).toBe(0);
            expect(liveSessionState.players).toEqual([]);
        });
    });

    describe('getPlayerCoordinates', () => {
        const player = {split_floors: [-10.5, 20.25, 3], facade: [-50, 60, 9]};

        test('getPlayerCoordinates_givenFacade_returnsTheFacadeCoordinates', () => {
            // Act
            const coordinates = LiveSessionState.getPlayerCoordinates(player, true);

            // Assert
            expect(coordinates).toEqual({lat: -50, lng: 60, floor_id: 9});
        });

        test('getPlayerCoordinates_givenSplitFloors_returnsTheSplitFloorCoordinates', () => {
            // Act
            const coordinates = LiveSessionState.getPlayerCoordinates(player, false);

            // Assert
            expect(coordinates).toEqual({lat: -10.5, lng: 20.25, floor_id: 3});
        });
    });
});
