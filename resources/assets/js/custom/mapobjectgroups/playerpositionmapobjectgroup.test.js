// Every live session state carries every player, so createOrUpdatePlayerPosition() is called for players that did
// not move. The MapObjectGroup base is stubbed down to what the method touches.

global.MAP_OBJECT_GROUP_PLAYER_POSITION = 'playerposition';
global.MapObjectGroup = class MapObjectGroup {
    constructor(manager) {
        this.manager = manager;
        this.objects = {};
    }

    loadMapObject(data) {
        const playerPosition = {
            id: data.id,
            player_guid: data.id,
            moveTo: vi.fn(),
            setClassSpecialization: vi.fn(),
            shouldBeVisible: () => true,
        };
        this.objects[data.id] = playerPosition;

        return playerPosition;
    }

    setMapObjectVisibility() {
    }
};

const {PlayerPositionMapObjectGroup} = require('./playerpositionmapobjectgroup.js');

describe('PlayerPositionMapObjectGroup', () => {
    test('createOrUpdatePlayerPosition_givenAPlayerThatDidNotMove_leavesItsMarkerAlone', () => {
        // Arrange
        const group = new PlayerPositionMapObjectGroup({}, false);
        group.createOrUpdatePlayerPosition('Player-1-A', 'Herochar', -10, 20, 3, 7, 262, 'icon.png');
        const playerPosition = group.objects['Player-1-A'];

        // Act
        group.createOrUpdatePlayerPosition('Player-1-A', 'Herochar', -10, 20, 3, 7, 262, 'icon.png');

        // Assert
        expect(playerPosition.moveTo).not.toHaveBeenCalled();
        expect(playerPosition.setClassSpecialization).not.toHaveBeenCalled();
    });

    test('createOrUpdatePlayerPosition_givenAPlayerThatMoved_movesItsMarker', () => {
        // Arrange
        const group = new PlayerPositionMapObjectGroup({}, false);
        group.createOrUpdatePlayerPosition('Player-1-A', 'Herochar', -10, 20, 3, 7, 262, 'icon.png');
        const playerPosition = group.objects['Player-1-A'];

        // Act
        group.createOrUpdatePlayerPosition('Player-1-A', 'Herochar', -11, 20, 3, 7, 262, 'icon.png');
        group.createOrUpdatePlayerPosition('Player-1-A', 'Herochar', -11, 20, 3, 7, 262, 'icon.png');

        // Assert
        expect(playerPosition.moveTo).toHaveBeenCalledTimes(1);
        expect(playerPosition.moveTo).toHaveBeenCalledWith(-11, 20, 3);
    });

    test('getRawObjects_givenAnyMapContext_leavesThePlayersToTheLiveSessionState', () => {
        // Arrange
        const group = new PlayerPositionMapObjectGroup({}, false);

        // Act
        const rawObjects = group._getRawObjects();

        // Assert
        expect(rawObjects).toEqual([]);
    });
});
