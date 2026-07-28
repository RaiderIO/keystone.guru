// ---------------------------------------------------------------------------
// Regression coverage for #128.
//
// Leaflet's _addFocusListenersOnLayer() runs on both the bind and the unbind
// path of _initTooltipInteractions() and only ever adds a raw DOM 'focus'
// listener, which dereferences this._tooltip without a null-guard. Any layer
// whose tooltip is unbound while it stays on the map therefore throws
// "Cannot set properties of null (setting '_source')" the next time its element
// receives focus - and clicking a marker focuses it, markers being focusable by
// default.
//
// The first test is the control: it proves stock leaflet really does throw, so a
// leaflet upgrade that fixes this upstream turns that test red rather than
// leaving the shim silently pointless.
// ---------------------------------------------------------------------------

const {applyLeafletTooltipFocusFix} = require('./leaflet-tooltip-focus-fix');

/**
 * A fresh, unpatched leaflet module. Leaflet mutates its own prototypes, so every test that patches
 * it needs its own copy - hence the require-cache reset.
 *
 * @returns {Object}
 */
function loadLeaflet() {
    delete require.cache[require.resolve('leaflet')];

    return require('leaflet');
}

/**
 * Builds a map with a single marker on it and returns both, ready for tooltip binding.
 *
 * @param {Object} leaflet
 * @returns {{map: Object, marker: Object}}
 */
function createMapWithMarker(leaflet) {
    const container = document.createElement('div');
    // Leaflet refuses to initialise on a zero-sized container in some code paths.
    Object.defineProperty(container, 'clientWidth', {value: 800});
    Object.defineProperty(container, 'clientHeight', {value: 600});
    document.body.appendChild(container);

    const map = leaflet.map(container, {center: [0, 0], zoom: 1, zoomControl: false, attributionControl: false});
    const marker = leaflet.marker([0, 0]).addTo(map);

    return {map, marker};
}

/**
 * Dispatches a native focus event at the marker's icon and returns the error it threw, if any.
 * Listeners added through DomEvent throw asynchronously to the caller, so the error surfaces on
 * window rather than out of dispatchEvent().
 *
 * @param {Object} marker
 * @returns {?string}
 */
function focusMarkerIcon(marker) {
    let message = null;
    const onError = (event) => {
        message = event.error ? event.error.message : event.message;
        event.preventDefault();
    };

    window.addEventListener('error', onError);
    try {
        marker.getElement().dispatchEvent(new FocusEvent('focus'));
    } catch (error) {
        message = error.message;
    } finally {
        window.removeEventListener('error', onError);
    }

    return message;
}

describe('leaflet tooltip focus listener (#128)', () => {
    test('focusListener_givenStockLeafletAndAnUnboundTooltip_throws', () => {
        const leaflet = loadLeaflet();
        const {marker} = createMapWithMarker(leaflet);

        marker.bindTooltip('Some enemy');
        marker.unbindTooltip();

        expect(focusMarkerIcon(marker)).toContain("Cannot set properties of null (setting '_source')");
    });

    test('focusListener_givenThePatchAndAnUnboundTooltip_doesNotThrow', () => {
        const leaflet = loadLeaflet();
        applyLeafletTooltipFocusFix(leaflet);
        const {marker} = createMapWithMarker(leaflet);

        marker.bindTooltip('Some enemy');
        marker.unbindTooltip();

        expect(focusMarkerIcon(marker)).toBeNull();
    });

    test('focusListener_givenThePatchAndABoundTooltip_stillOpensTheTooltip', () => {
        const leaflet = loadLeaflet();
        applyLeafletTooltipFocusFix(leaflet);
        const {marker} = createMapWithMarker(leaflet);

        marker.bindTooltip('Some enemy');

        expect(focusMarkerIcon(marker)).toBeNull();
        expect(marker.isTooltipOpen()).toBe(true);
        expect(marker.getTooltip()._source).toBe(marker);
    });

    test('focusListener_givenThePatchAndARebindAfterUnbinding_stillOpensTheTooltip', () => {
        const leaflet = loadLeaflet();
        applyLeafletTooltipFocusFix(leaflet);
        const {marker} = createMapWithMarker(leaflet);

        marker.bindTooltip('Some enemy');
        marker.unbindTooltip();
        marker.bindTooltip('Some other enemy');

        expect(focusMarkerIcon(marker)).toBeNull();
        expect(marker.isTooltipOpen()).toBe(true);
    });
});
