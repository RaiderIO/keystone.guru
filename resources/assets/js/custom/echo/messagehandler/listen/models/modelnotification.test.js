// The "changed by"/"deleted by" notifications are rendered as HTML (Noty sets them through innerHTML),
// while the user name and the object's text come from another user.

global.BaseModelHandler = class BaseModelHandler {
};

global.Handlebars = require('handlebars');

const {ModelChangedHandler} = require('./modelchangedhandler');
const {ModelDeletedHandler} = require('./modeldeletedhandler');

const MESSAGES = {
    'js.echo_object_changed_notification': ':object was changed by :user',
    'js.echo_object_deleted_notification': ':object was deleted by :user',
};

describe.each([
    ['ModelChangedHandler._showChangedFromEchoNotification', () => new ModelChangedHandler(), '_showChangedFromEchoNotification', 'changed'],
    ['ModelDeletedHandler._showDeletedFromEcho', () => new ModelDeletedHandler(), '_showDeletedFromEcho', 'deleted'],
])('%s', (_, makeHandler, method, verb) => {
    let originalLang;
    let originalGetState;

    beforeEach(() => {
        originalLang = global.lang;
        originalGetState = global.getState;

        global.lang = {
            get: (key, replacements = {}) => Object.entries(replacements).reduce(
                (message, [name, value]) => message.replace(`:${name}`, value),
                MESSAGES[key],
            ),
        };
        global.getState = () => ({
            isEchoEnabled: () => true,
            getUser: () => ({public_key: 'local-user'}),
        });
        global.showInfoNotification = vi.fn();
    });

    afterEach(() => {
        global.lang = originalLang;
        global.getState = originalGetState;
        delete global.showInfoNotification;
    });

    it('escapes the user name and the object text', () => {
        // Arrange
        const handler = makeHandler();
        const localMapObject = {toString: () => '<i>comment</i>'};
        const user = {public_key: 'remote-user', name: '<img src=x onerror=alert(1)>'};

        // Act
        handler[method](localMapObject, user);

        // Assert
        expect(global.showInfoNotification).toHaveBeenCalledWith(
            `&lt;i&gt;comment&lt;/i&gt; was ${verb} by &lt;img src&#x3D;x onerror&#x3D;alert(1)&gt;`,
        );
    });

    it('fills in a plain user name and object text unchanged', () => {
        // Arrange
        const handler = makeHandler();

        // Act
        handler[method]({toString: () => 'Pull 3'}, {public_key: 'remote-user', name: 'Wotuu'});

        // Assert
        expect(global.showInfoNotification).toHaveBeenCalledWith(`Pull 3 was ${verb} by Wotuu`);
    });

    it('shows nothing for a change made by the local user', () => {
        // Arrange
        const handler = makeHandler();

        // Act
        handler[method]({toString: () => 'Pull 3'}, {public_key: 'local-user', name: 'Wotuu'});

        // Assert
        expect(global.showInfoNotification).not.toHaveBeenCalled();
    });
});
