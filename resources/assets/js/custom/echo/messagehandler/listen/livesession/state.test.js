// The base MessageHandler is stubbed: what is under test is that the handler hands the broadcast state to the map's
// LiveSessionStateManager, whoever caused the broadcast.

global.MessageHandler = class MessageHandler {
    constructor(echo, message) {
        this.echo = echo;
        this.message = message;
    }

    onReceive(e) {
    }
};
global.LiveSessionStateMessage = class LiveSessionStateMessage {
    static getName() {
        return 'livesession-state';
    }
};

const {LiveSessionStateHandler} = require('./state.js');

const state = {killed: [5], in_combat: [], obsolete: [], overpulled: [[6, 7]], players: []};

describe('LiveSessionStateHandler', () => {
    test('constructor_givenAnEcho_listensForTheStateBroadcast', () => {
        // Act
        const handler = new LiveSessionStateHandler({map: {}});

        // Assert
        expect(handler.message).toBe('livesession-state');
    });

    test('onReceive_givenAStateBroadcast_handsTheStateToTheMap', () => {
        // Arrange
        const setState = vi.fn();
        const handler = new LiveSessionStateHandler({map: {liveSessionStateManager: {setState: setState}}});

        // Act
        handler.onReceive({__name: 'livesession-state', user: {public_key: 'me'}, state: state});

        // Assert
        expect(setState).toHaveBeenCalledTimes(1);
        expect(setState).toHaveBeenCalledWith(state);
    });

    test('onReceive_givenAMapWithoutLiveSession_ignoresTheBroadcast', () => {
        // Arrange
        const handler = new LiveSessionStateHandler({map: {liveSessionStateManager: null}});

        // Act + Assert
        expect(() => handler.onReceive({__name: 'livesession-state', state: state})).not.toThrow();
    });
});
