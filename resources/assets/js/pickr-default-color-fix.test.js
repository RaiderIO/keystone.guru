// ---------------------------------------------------------------------------
// Regression coverage for the Pickr default colour fix.
//
// @simonwep/pickr 1.10.2 initialises a picker from its own `_color`, which starts
// out as black, so the `default` option is never applied: the colour picker in a
// map object's popup showed black instead of the object's colour.
//
// The first test is the control: it proves stock Pickr really does ignore
// `default`, so an upgrade that fixes this upstream turns that test red rather
// than leaving the shim silently pointless.
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

describe('Pickr default colour fix', () => {
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

    test('create_givenStockPickr_ignoresTheDefaultColor', async () => {
        // Arrange & Act
        const pickr = await createInitialisedPickr(Pickr, '#8d14c5');

        // Assert
        expect(pickr.getColor().toHEXA().toString()).toBe('#000000');
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

        // Act
        await new Promise(resolve => {
            FixedPickr.create({el: button, theme: 'nano', default: '#8d14c5'})
                .on('save', onSave)
                .on('init', () => setTimeout(resolve));
        });

        // Assert
        expect(onSave).not.toHaveBeenCalled();
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
