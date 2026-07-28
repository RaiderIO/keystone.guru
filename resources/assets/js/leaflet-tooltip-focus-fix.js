// ---------------------------------------------------------------------------
// Leaflet tooltip focus-listener fix (#128)
//
// Upstream bug in leaflet 1.9.4 (src/layer/Tooltip.js): every layer that has a
// tooltip gets a raw DOM 'focus' listener on its element, added by
// _addFocusListenersOnLayer(). That helper is called from
// _initTooltipInteractions(), which runs on BOTH the bind and the unbind path -
// and it only ever ADDS a listener, it never removes one. So:
//
//   layer.bindTooltip(...)    -> focus listener #1
//   layer.unbindTooltip()     -> focus listener #2, then this._tooltip = null
//
// The listener body is `this._tooltip._source = layer; this.openTooltip();`,
// with no null-guard - unlike its sibling _openTooltip(), which starts with
// `if (!this._tooltip || !this._map) { return; }`. Once unbindTooltip() has
// nulled _tooltip, the next native focus event on that element therefore throws
//
//     Uncaught TypeError: Cannot set properties of null (setting '_source')
//
// and a plain mouse click produces exactly such an event, because Leaflet's
// default `keyboard: true` marker option gives every marker icon tabindex="0".
//
// The app unbinds tooltips from still-live layers in several places (a layer
// swap in MapObjectGroup#setLayerToMapObject, the raid-marker circle menu in
// EnemyVisual, ...), so this is not specific to any one feature. Re-declare the
// method with the guard its sibling already has.
//
// Layer.include() merges into L.Layer.prototype, which is where Tooltip.js puts
// these methods, so this must run after leaflet has been required.
// ---------------------------------------------------------------------------

/**
 * Applies the null-guard to the given leaflet module's tooltip focus listener.
 *
 * @param {Object} leaflet The leaflet module (`require('leaflet')`).
 * @returns {void}
 */
function applyLeafletTooltipFocusFix(leaflet) {
    leaflet.Layer.include({
        _addFocusListenersOnLayer: function (layer) {
            const element = typeof layer.getElement === 'function' && layer.getElement();
            if (!element) {
                return;
            }

            leaflet.DomEvent.on(element, 'focus', function () {
                // The only change from upstream: a stale listener whose tooltip has since been
                // unbound must do nothing instead of throwing.
                if (!this._tooltip) {
                    return;
                }

                this._tooltip._source = layer;
                this.openTooltip();
            }, this);
            leaflet.DomEvent.on(element, 'blur', this.closeTooltip, this);
        },
    });
}

module.exports = {applyLeafletTooltipFocusFix};
