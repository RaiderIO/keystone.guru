// Cursor and viewport whispers travel over the map's presence channel. Reverb stamps the sending connection's
// presence member id onto each client event as user_id, which pusher-js hands to the bound callback as
// metadata.user_id; the handlers resolve the sender from that member and never from the payload.
//
// The echo sources are global scripts: define the globals they touch at load time, then require() them in
// dependency order.
const {Signalable} = require('../../../signalable');

global.Signalable = Signalable;
global.Message = require('../../message/message');
global.MousePositionMessage = require('../../message/whisper/mouseposition');
global.ViewPortMessage = require('../../message/whisper/viewport');
global.MessageHandler = require('../messagehandler');

// The real MessageFactory switches over every message class in the bundle; only the whisper messages matter here.
global.MessageFactory = class MessageFactory {
    create(name, props) {
        if (name === MousePositionMessage.getName()) {
            return new MousePositionMessage(props);
        } else if (name === ViewPortMessage.getName()) {
            return new ViewPortMessage(props);
        }

        return null;
    }
};

global.WhisperMessageHandler = require('./whispermessagehandler');
const MousePositionHandler = require('./mouseposition');
const ViewPortHandler = require('./viewport');

const OWN_PUBLIC_KEY = 'own-key';
const ALICE_PUBLIC_KEY = 'alice-key';
const BOB_PUBLIC_KEY = 'bob-key';
const ALICE_MEMBER_ID = '11';
const BOB_MEMBER_ID = '22';

beforeEach(() => {
    global.getState = () => ({
        getUser: () => ({public_key: OWN_PUBLIC_KEY}),
        getCurrentFloor: () => ({id: 1}),
    });
    // Skips the mouse polling interval; receiving does not depend on it
    global.isMobile = () => true;
});

function createEchoUser(publicKey) {
    return {
        center: null,
        zoom: null,
        getPublicKey: () => publicKey,
        setFloorId: vi.fn(),
        setCenter(center) {
            this.center = center;
        },
        setZoom(zoom) {
            this.zoom = zoom;
        },
        mapobject: {
            onPositionsReceived: vi.fn(),
            setSynced: vi.fn(),
        },
    };
}

/**
 * Mirrors laravel-echo's PusherPresenceChannel: listenForWhisper() binds the callback to pusher-js, which calls it
 * with (data, metadata), and subscription.members is pusher-js' Members.
 */
function createPresenceChannel(membersById) {
    return {
        whisperListeners: {},
        whispered: [],
        subscription: {
            members: {
                get: (id) => Object.prototype.hasOwnProperty.call(membersById, id) ? {id: id, info: membersById[id]} : null,
            },
        },
        listenForWhisper(event, callback) {
            this.whisperListeners[event] = callback;

            return this;
        },
        whisper(event, data) {
            this.whispered.push({event: event, data: data});

            return this;
        },
        receiveWhisper(event, data, metadata) {
            this.whisperListeners[event]?.(data, metadata);
        },
    };
}

function createEcho(echoUsers) {
    return {
        map: {
            options: {edit: true},
            register: () => {
            },
            leafletMap: {
                on: () => {
                },
                off: () => {
                },
                getCenter: () => ({lat: 10, lng: 20}),
                getZoom: () => 3,
            },
        },
        getUserByPublicKey: (publicKey) => echoUsers.find((echoUser) => echoUser.getPublicKey() === publicKey) ?? null,
        onMousePositionReceived: vi.fn(),
        isFollowingUser: () => false,
    };
}

function createSetup() {
    const alice = createEchoUser(ALICE_PUBLIC_KEY);
    const bob = createEchoUser(BOB_PUBLIC_KEY);
    const echo = createEcho([alice, bob]);
    const presenceChannel = createPresenceChannel({
        [ALICE_MEMBER_ID]: {public_key: ALICE_PUBLIC_KEY, name: 'Alice'},
        [BOB_MEMBER_ID]: {public_key: BOB_PUBLIC_KEY, name: 'Bob'},
    });

    return {alice, bob, echo, presenceChannel};
}

describe('MousePositionHandler', () => {
    test('onWhisperReceived_givenAPayloadNamingAnotherUser_returnsThePositionsOnTheMemberReverbStamped', () => {
        // Arrange
        const {alice, bob, echo, presenceChannel} = createSetup();
        const handler = new MousePositionHandler(echo);
        handler.setup(presenceChannel);
        const points = [{time: 0, lat: 1, lng: 2}];

        // Act
        presenceChannel.receiveWhisper(handler.getMessage(), {
            user: {public_key: ALICE_PUBLIC_KEY},
            floor_id: 7,
            points: points,
        }, {user_id: BOB_MEMBER_ID});

        // Assert
        expect(alice.mapobject.onPositionsReceived).not.toHaveBeenCalled();
        expect(bob.mapobject.onPositionsReceived).toHaveBeenCalledTimes(1);
        const message = bob.mapobject.onPositionsReceived.mock.calls[0][0];
        expect(message.points).toEqual(points);
        expect(message.user).toEqual({public_key: BOB_PUBLIC_KEY});
        expect(bob.setFloorId).toHaveBeenCalledWith(7);
        expect(echo.onMousePositionReceived).toHaveBeenCalledTimes(1);
        expect(echo.onMousePositionReceived.mock.calls[0][0].data.message.user).toEqual({public_key: BOB_PUBLIC_KEY});
    });

    test('onWhisperReceived_givenNoStampedMember_dropsTheWhisper', () => {
        // Arrange
        const {alice, bob, echo, presenceChannel} = createSetup();
        const handler = new MousePositionHandler(echo);
        handler.setup(presenceChannel);
        const payload = {user: {public_key: ALICE_PUBLIC_KEY}, floor_id: 7, points: [{time: 0, lat: 1, lng: 2}]};

        // Act
        presenceChannel.receiveWhisper(handler.getMessage(), payload, {});
        presenceChannel.receiveWhisper(handler.getMessage(), payload, undefined);
        presenceChannel.receiveWhisper(handler.getMessage(), payload, {user_id: '999'});
        presenceChannel.receiveWhisper(handler.getMessage(), payload, {user_id: ALICE_MEMBER_ID});

        // Assert - only the last whisper carries a member id that is present on the channel
        expect(alice.mapobject.onPositionsReceived).toHaveBeenCalledTimes(1);
        expect(bob.mapobject.onPositionsReceived).not.toHaveBeenCalled();
        expect(echo.onMousePositionReceived).toHaveBeenCalledTimes(1);
    });

    test('onWhisperReceived_givenAMemberWithoutAPublicKey_dropsTheWhisper', () => {
        // Arrange - a channel whose authorization answers true instead of member info yields a member, and an
        // EchoUser, without a public key
        const keylessUser = createEchoUser(undefined);
        const bob = createEchoUser(BOB_PUBLIC_KEY);
        const echo = createEcho([keylessUser, bob]);
        const presenceChannel = createPresenceChannel({[ALICE_MEMBER_ID]: true, [BOB_MEMBER_ID]: {public_key: BOB_PUBLIC_KEY}});
        const handler = new MousePositionHandler(echo);
        handler.setup(presenceChannel);
        const payload = {floor_id: 7, points: []};

        // Act
        presenceChannel.receiveWhisper(handler.getMessage(), payload, {user_id: ALICE_MEMBER_ID});
        presenceChannel.receiveWhisper(handler.getMessage(), payload, {user_id: BOB_MEMBER_ID});

        // Assert
        expect(keylessUser.mapobject.onPositionsReceived).not.toHaveBeenCalled();
        expect(bob.mapobject.onPositionsReceived).toHaveBeenCalledTimes(1);
    });

    test('send_givenAMousePositionMessage_whispersOnThePresenceChannelWithoutAUser', () => {
        // Arrange
        const {echo, presenceChannel} = createSetup();
        const handler = new MousePositionHandler(echo);
        handler.setup(presenceChannel);

        // Act
        handler.send(new MousePositionMessage({points: [{time: 0, lat: 1, lng: 2}]}));

        // Assert
        expect(presenceChannel.whispered).toHaveLength(1);
        expect(presenceChannel.whispered[0].event).toBe(handler.getMessage());
        expect(presenceChannel.whispered[0].data.points).toEqual([{time: 0, lat: 1, lng: 2}]);
        expect(presenceChannel.whispered[0].data).not.toHaveProperty('user');
    });
});

describe('ViewPortHandler', () => {
    test('onWhisperReceived_givenAPayloadNamingAnotherUser_returnsTheViewportOnTheMemberReverbStamped', () => {
        // Arrange
        const {alice, bob, echo, presenceChannel} = createSetup();
        const handler = new ViewPortHandler(echo);
        handler.setup(presenceChannel);

        // Act
        presenceChannel.receiveWhisper(handler.getMessage(), {
            user: {public_key: ALICE_PUBLIC_KEY},
            floor_id: 7,
            center: {lat: 5, lng: 6},
            zoom: 4,
        }, {user_id: BOB_MEMBER_ID});

        // Assert
        expect(alice.center).toBeNull();
        expect(alice.zoom).toBeNull();
        expect(bob.center).toEqual({lat: 5, lng: 6});
        expect(bob.zoom).toBe(4);
        expect(bob.setFloorId).toHaveBeenCalledWith(7);
    });

    test('onWhisperReceived_givenNoStampedMember_dropsTheWhisper', () => {
        // Arrange
        const {alice, bob, echo, presenceChannel} = createSetup();
        const handler = new ViewPortHandler(echo);
        handler.setup(presenceChannel);
        const payload = {user: {public_key: ALICE_PUBLIC_KEY}, floor_id: 7, center: {lat: 5, lng: 6}, zoom: 4};

        // Act
        presenceChannel.receiveWhisper(handler.getMessage(), payload, {});
        presenceChannel.receiveWhisper(handler.getMessage(), {...payload, zoom: 8}, {user_id: BOB_MEMBER_ID});

        // Assert - the stamped whisper lands, the unstamped one does not
        expect(alice.zoom).toBeNull();
        expect(alice.setFloorId).not.toHaveBeenCalled();
        expect(bob.zoom).toBe(8);
    });

    test('send_givenTheMapViewport_whispersOnThePresenceChannelWithoutAUser', () => {
        // Arrange
        const {echo, presenceChannel} = createSetup();
        const handler = new ViewPortHandler(echo);
        handler.setup(presenceChannel);

        // Act
        handler._sendViewPort();

        // Assert
        expect(presenceChannel.whispered).toHaveLength(1);
        expect(presenceChannel.whispered[0].data.center).toEqual({lat: 10, lng: 20});
        expect(presenceChannel.whispered[0].data.zoom).toBe(3);
        expect(presenceChannel.whispered[0].data).not.toHaveProperty('user');
    });
});
