// EchoHandler joins the map's presence channel through Laravel Echo. Laravel Echo, the presence channel and every
// message handler are stubbed; what is under test is the signal the handler gives for each successful subscription.

const {Signalable} = require('../signalable.js');
global.Signalable = Signalable;
global.DungeonMap = class DungeonMap {
};

const handlerClassNames = [
    'InviteHandler', 'StopHandler', 'LiveSessionStateHandler',
    'BrushlineChangedHandler', 'BrushlineDeletedHandler',
    'ArrowChangedHandler', 'ArrowDeletedHandler',
    'KillZoneChangedHandler', 'KillZoneDeletedHandler',
    'MapIconChangedHandler', 'MapIconDeletedHandler',
    'NpcChangedHandler', 'NpcDeletedHandler',
    'PathChangedHandler', 'PathDeletedHandler',
    'UserColorChangedHandler',
    'MousePositionHandler', 'ViewPortHandler',
];
for (const handlerClassName of handlerClassNames) {
    global[handlerClassName] = class {
        setup() {
        }
    };
}

const EchoHandler = require('./echohandler.js');

/**
 * A presence channel that remembers the callbacks registered on it, so a test can play Pusher's part.
 */
function makePresenceChannel() {
    const channel = {callbacks: {}};
    for (const method of ['here', 'joining', 'leaving', 'error']) {
        channel[method] = (callback) => {
            channel.callbacks[method] = callback;
            return channel;
        };
    }
    return channel;
}

function arrangeEchoHandler() {
    const presenceChannel = makePresenceChannel();
    window.startLaravelEcho = () => {
    };
    window.LaravelEcho = {
        connector: {pusher: {connection: {bind: () => {
        }}}},
        join: () => presenceChannel,
    };
    global.getState = () => ({
        getMapContext: () => ({getEchoChannelName: () => 'route-edit.route'}),
    });

    const echoHandler = new EchoHandler(new DungeonMap());
    const subscriptions = [];
    echoHandler.register('presence:subscribed', subscriptions, () => subscriptions.push('subscribed'));

    return {echoHandler, presenceChannel, subscriptions};
}

describe('EchoHandler', () => {
    test('connect_givenThePresenceChannelSubscribed_signalsTheSubscription', () => {
        // Arrange
        const {echoHandler, presenceChannel, subscriptions} = arrangeEchoHandler();

        // Act
        echoHandler.connect();
        presenceChannel.callbacks.here([]);

        // Assert
        expect(subscriptions).toEqual(['subscribed']);
    });

    test('connect_givenThePresenceChannelSubscribedAgainAfterAReconnect_signalsEverySubscription', () => {
        // Arrange
        const {echoHandler, presenceChannel, subscriptions} = arrangeEchoHandler();
        echoHandler.connect();
        presenceChannel.callbacks.here([]);

        // Act
        presenceChannel.callbacks.here([]);

        // Assert
        expect(subscriptions).toEqual(['subscribed', 'subscribed']);
    });

    test('connect_givenNoSubscriptionYet_signalsNothing', () => {
        // Arrange
        const {echoHandler, subscriptions} = arrangeEchoHandler();

        // Act
        echoHandler.connect();

        // Assert
        expect(subscriptions).toEqual([]);
    });
});
