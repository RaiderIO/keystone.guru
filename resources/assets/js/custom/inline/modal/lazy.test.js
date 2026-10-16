// ---------------------------------------------------------------------------
// ModalLazy fetches a modal's body over ajax the first time it is shown. `$.ajax` is stubbed to
// answer synchronously; `refreshSelectPickers`/`refreshTooltips` are globals in the bundle.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.refreshSelectPickers = vi.fn();
globalThis.refreshTooltips = vi.fn();

const {ModalLazy} = require('./lazy');

describe('ModalLazy', () => {
    let ajaxCalls;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="lazy_modal"><div class="probootstrap-modal-content">loading</div></div>`;
        ajaxCalls = [];
        $.ajax = (settings) => {
            ajaxCalls.push(settings);
            settings.success('<div id="loaded_form">form</div>');
        };

        new ModalLazy('lazy', 'modal/lazy', {id: '#lazy_modal', ajax_url: '/ajax/view/form'}).activate();
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('shown_givenTheContentLoaded_triggersModallazyLoadedOnceTheContentIsInPlace', () => {
        // Arrange
        const contentAtLoadedEvent = [];
        $('#lazy_modal').on('modallazy:loaded', () => contentAtLoadedEvent.push($('#loaded_form').length));

        // Act
        $('#lazy_modal').trigger('shown.bs.modal');

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect(contentAtLoadedEvent).toEqual([1]);
    });

    it('shown_givenASecondShow_neitherReloadsNorTriggersModallazyLoadedAgain', () => {
        // Arrange
        const loadedListener = vi.fn();
        $('#lazy_modal').on('modallazy:loaded', loadedListener);
        $('#lazy_modal').trigger('shown.bs.modal');

        // Act
        $('#lazy_modal').trigger('shown.bs.modal');

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect(loadedListener).toHaveBeenCalledTimes(1);
    });
});
