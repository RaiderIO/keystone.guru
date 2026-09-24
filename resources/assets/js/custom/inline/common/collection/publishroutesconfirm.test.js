// ---------------------------------------------------------------------------
// `publishroutesconfirm.js` is concatenated into a bundle in the browser and references its
// collaborators as bare globals. `$.ajax`, the Bootstrap modal and the notifications are replaced
// per test.
// ---------------------------------------------------------------------------

const jQuery = require('jquery');
const Lang   = require('lang.js');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonCollectionPublishroutesconfirm} = require('./publishroutesconfirm');

const MESSAGES = {
    'en.js': {
        publish_state_title_world:                          'Public',
        collection_publish_routes_confirm_success_one:       '1 route is now :state',
        collection_publish_routes_confirm_success_many:      ':count routes are now :state',
        collection_publish_routes_confirm_none_raised:       'None of those routes could be made :state - you may not own them',
        collection_publish_routes_confirm_save_failed:       'Unable to make those routes :state',
    },
};

describe('CommonCollectionPublishroutesconfirm', () => {
    let previousGlobals;
    let ajaxCalls;
    let modalHide;
    let code;

    beforeEach(() => {
        previousGlobals = {
            $:                       globalThis.$,
            lang:                    globalThis.lang,
            bootstrap:               globalThis.bootstrap,
            showSuccessNotification: globalThis.showSuccessNotification,
            showInfoNotification:    globalThis.showInfoNotification,
            showErrorNotification:   globalThis.showErrorNotification,
        };
        globalThis.$ = jQuery;

        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});

        ajaxCalls = [];
        jQuery.ajax = vi.fn((settings) => {
            ajaxCalls.push(settings);
        });

        modalHide = vi.fn();
        globalThis.bootstrap = {Modal: {getOrCreateInstance: () => ({hide: modalHide})}};

        globalThis.showSuccessNotification = vi.fn();
        globalThis.showInfoNotification    = vi.fn();
        globalThis.showErrorNotification   = vi.fn();

        document.body.innerHTML = `
            <div id="modal">
                <button id="yes_button">Yes</button>
            </div>`;

        code = new CommonCollectionPublishroutesconfirm('publish', 'common/collection/publishroutesconfirm', {
            modalSelector:     '#modal',
            yesButtonSelector: '#yes_button',
            publishUrl:        '/ajax/collection/colA/routes/publish',
            publishedState:    'world',
        });
        code.activate();
    });

    afterEach(() => {
        Object.assign(globalThis, previousGlobals);
    });

    it('yesClicked_postsToThePublishUrlWithNoBody', () => {
        // Arrange

        // Act
        jQuery('#yes_button').trigger('click');

        // Assert
        expect(ajaxCalls[0].type).toBe('POST');
        expect(ajaxCalls[0].url).toBe('/ajax/collection/colA/routes/publish');
        expect(ajaxCalls[0].data).toBeUndefined();
        expect(jQuery('#yes_button').prop('disabled')).toBe(true);
    });

    it('yesClicked_givenRoutesWereRaised_hidesTheModalAndSaysHowMany', () => {
        // Arrange

        // Act
        jQuery('#yes_button').trigger('click');
        ajaxCalls.shift().success({raised_count: 3, skipped_count: 1});

        // Assert
        expect(modalHide).toHaveBeenCalled();
        expect(showSuccessNotification).toHaveBeenCalledWith('3 routes are now Public');
    });

    it('yesClicked_givenExactlyOneRouteWasRaised_usesTheSingularWording', () => {
        // Arrange

        // Act
        jQuery('#yes_button').trigger('click');
        ajaxCalls.shift().success({raised_count: 1, skipped_count: 0});

        // Assert
        expect(showSuccessNotification).toHaveBeenCalledWith('1 route is now Public');
    });

    it('yesClicked_givenEveryRouteWasSkipped_saysNoneCouldBeRaised', () => {
        // Arrange

        // Act
        jQuery('#yes_button').trigger('click');
        ajaxCalls.shift().success({raised_count: 0, skipped_count: 2});

        // Assert
        expect(modalHide).toHaveBeenCalled();
        expect(showInfoNotification).toHaveBeenCalledWith('None of those routes could be made Public - you may not own them');
    });

    it('yesClicked_givenTheSaveFails_reEnablesTheButtonAndShowsAnError', () => {
        // Arrange

        // Act
        jQuery('#yes_button').trigger('click');
        ajaxCalls.shift().error({});

        // Assert
        expect(jQuery('#yes_button').prop('disabled')).toBe(false);
        expect(showErrorNotification).toHaveBeenCalledWith('Unable to make those routes Public');
        expect(modalHide).not.toHaveBeenCalled();
    });
});
