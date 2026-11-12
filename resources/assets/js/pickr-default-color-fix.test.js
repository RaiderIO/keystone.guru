// ---------------------------------------------------------------------------
// Regression coverage for the Pickr undefined default colour guard.
//
// The map object popups, the pull settings and the freedraw settings open
// their colour picker on the `default` option, so stock Pickr must keep
// honouring it. An explicit `default: undefined` makes @simonwep/pickr 1.10.3
// throw in its setup frame, which the guard prevents.
//
// The stock Pickr tests are the controls: an upgrade that breaks `default`
// again, or one that stops throwing on undefined, turns them red rather than
// leaving the guard silently wrong or pointless.
// ---------------------------------------------------------------------------

import Pickr from '@simonwep/pickr';

const {withPickrDefaultColorFix} = require('./pickr-default-color-fix');

/**
 * Creates a picker on a fresh button and resolves once it has initialised.
 *
 * @param {Function} PickrClass
 * @param {?string} defaultColor
 * @returns {Promise<Object>}
 */
function createInitialisedPickr(PickrClass, defaultColor) {
    const button = document.createElement('button');
    document.body.appendChild(button);

    return new Promise(resolve => {
        const pickr = PickrClass.create({
            el: button,
            theme: 'nano',
            default: defaultColor,
            components: {preview: true, opacity: true, hue: true, interaction: {hex: true, input: true, save: true}},
        });
        pickr.on('init', () => setTimeout(() => resolve(pickr)));
    });
}

/**
 * Creates a picker with an undefined default colour and lets its setup frame run, collecting the
 * errors it raises instead of waiting for an 'init' that may never come.
 *
 * @param {Function} PickrClass
 * @returns {Promise<{pickr: Object, errors: string[], initialised: boolean}>}
 */
async function createPickrWithAnUndefinedDefaultColor(PickrClass) {
    const button = document.createElement('button');
    document.body.appendChild(button);
    const errors = [];
    const onError = event => {
        errors.push(event.error ? event.error.message : event.message);
        event.preventDefault();
    };
    window.addEventListener('error', onError);

    let initialised = false;
    const pickr = PickrClass.create({el: button, theme: 'nano', default: undefined})
        .on('init', () => {
            initialised = true;
        });
    await new Promise(resolve => setTimeout(resolve, 200));
    window.removeEventListener('error', onError);

    return {pickr, errors, initialised};
}

describe('Pickr undefined default colour guard', () => {
    beforeEach(() => {
        // The setup frame waits until the picker has a layout, and its sliders divide by their
        // size; jsdom lays out nothing
        vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(100);
        vi.spyOn(Element.prototype, 'getBoundingClientRect').mockReturnValue(
            {x: 0, y: 0, left: 0, top: 0, right: 100, bottom: 100, width: 100, height: 100}
        );
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('create_givenStockPickrAndADefaultColor_selectsTheDefaultColor', async () => {
        // Arrange & Act
        const pickr = await createInitialisedPickr(Pickr, '#8d14c5');

        // Assert
        expect(pickr.getColor().toHEXA().toString()).toBe('#8D14C5');
        expect(pickr.getSelectedColor().toHEXA().toString()).toBe('#8D14C5');
        expect(pickr.getRoot().button.style.getPropertyValue('--pcr-color')).toBe('rgba(141, 20, 197, 1)');
    });

    test('create_givenStockPickrAndAnUndefinedDefaultColor_throwsBeforeInit', async () => {
        // Arrange & Act
        const {errors, initialised} = await createPickrWithAnUndefinedDefaultColor(Pickr);

        // Assert
        expect(errors).toEqual(["Cannot read properties of undefined (reading 'match')"]);
        expect(initialised).toBe(false);
    });

    test('create_givenADefaultColor_selectsTheDefaultColor', async () => {
        // Arrange
        const FixedPickr = withPickrDefaultColorFix(Pickr);

        // Act
        const pickr = await createInitialisedPickr(FixedPickr, '#8d14c5');

        // Assert
        expect(pickr.getColor().toHEXA().toString()).toBe('#8D14C5');
        expect(pickr.getSelectedColor().toHEXA().toString()).toBe('#8D14C5');
        expect(pickr.getRoot().button.style.getPropertyValue('--pcr-color')).toBe('rgba(141, 20, 197, 1)');
    });

    test('create_givenADefaultColor_doesNotFireSave', async () => {
        // Arrange
        const FixedPickr = withPickrDefaultColorFix(Pickr);
        const button = document.createElement('button');
        document.body.appendChild(button);
        const onSave = vi.fn();
        const onInit = vi.fn();

        // Act
        await new Promise(resolve => {
            FixedPickr.create({el: button, theme: 'nano', default: '#8d14c5'})
                .on('save', onSave)
                .on('init', onInit)
                .on('init', () => setTimeout(resolve));
        });

        // Assert
        expect(onInit).toHaveBeenCalledTimes(1);
        expect(onSave).not.toHaveBeenCalled();
    });

    test('create_givenAnUndefinedDefaultColor_initialisesOnPickrsBuiltInDefaultWithoutThrowing', async () => {
        // Arrange
        const FixedPickr = withPickrDefaultColorFix(Pickr);

        // Act
        const {pickr, errors, initialised} = await createPickrWithAnUndefinedDefaultColor(FixedPickr);

        // Assert
        expect(errors).toEqual([]);
        expect(initialised).toBe(true);
        expect(pickr.getColor().toHEXA().toString()).toBe(Pickr.DEFAULT_OPTIONS.default.toUpperCase());
    });

    test('create_givenANullDefaultColor_clearsTheButton', async () => {
        // Arrange
        const FixedPickr = withPickrDefaultColorFix(Pickr);

        // Act
        const pickr = await createInitialisedPickr(FixedPickr, null);

        // Assert
        expect(pickr.getSelectedColor()).toBeNull();
        expect(pickr.getRoot().button.classList.contains('clear')).toBe(true);
    });
});
