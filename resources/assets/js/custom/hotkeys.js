// Leaflet's keyboard zoom-out also listens to keyCode 54, which is the `6` key - a draw tool hotkey.
L.Map.Keyboard.prototype.keyCodes.zoomOut = L.Map.Keyboard.prototype.keyCodes.zoomOut.filter((keyCode) => keyCode !== 54);

/**
 * @typedef {Object} DrawTool
 * @property {String} id Unique per editor; the Leaflet.draw handler type for draw tools
 * @property {String} [kind] 'draw' (default), 'pather', 'edit' or 'remove'
 * @property {String|null} [group] Tools sharing a group are rendered in one flyout
 * @property {Boolean} [hidden] Rendered invisibly, only activated programmatically
 * @property {String} [icon] Font Awesome class
 * @property {String} [label] Translation key of the button label
 * @property {String} [title] Translation key of the tooltip, receives :hotkey
 * @property {String[]} [keys] Chord strings such as `1` or `shift+u`; the first one is shown on the button
 * @property {String} [btnType] Bootstrap button class, defaults to btn-info
 * @property {Function} [handler] Leaflet.draw handler class (draw tools only)
 * @property {Object} [options] Leaflet.draw handler options (draw tools only)
 */

class Hotkeys extends Signalable {
    constructor(map) {
        super();
        this.map = map;

        this.map.register('map:refresh', this, (this._mapRefreshed).bind(this));
        /** @type {DrawTool[]} */
        this.tools = [];
    }

    /**
     * Called whenever leaflet map has been refreshed.
     * @param refreshEvent {Object}
     * @private
     */
    _mapRefreshed(refreshEvent) {
        console.assert(this instanceof Hotkeys, 'this is not an instance of Hotkeys', this);

        $(document).off('keydown.hotkeys').on('keydown.hotkeys', (this.onKeyPressed).bind(this));
    }

    /**
     * Replaces the tools whose keys are listened to.
     * @param tools {DrawTool[]}
     */
    setTools(tools) {
        this.tools = tools.filter((tool) => Array.isArray(tool.keys) && tool.keys.length > 0);
    }

    /**
     * Whether a keyboard event should be left alone because the user is typing or has a dialog open.
     * @param keyEvent {KeyboardEvent|jQuery.Event}
     * @returns {boolean}
     * @private
     */
    _shouldIgnoreKeyEvent(keyEvent) {
        if ($(keyEvent.target).closest('input, textarea, select, [contenteditable]').length > 0) {
            return true;
        }

        return $('.modal.show').length > 0 || this.map.hasPopupOpen();
    }

    /**
     * Called whenever a key was pressed anywhere on the page.
     * @param keyEvent {KeyboardEvent|jQuery.Event}
     */
    onKeyPressed(keyEvent) {
        console.assert(this instanceof Hotkeys, 'this is not an instance of Hotkeys', this);

        if (typeof keyEvent.key !== 'string' || this._shouldIgnoreKeyEvent(keyEvent)) {
            return;
        }

        let tool = Hotkeys.findToolForKeyEvent(this.tools, keyEvent);
        if (tool !== null) {
            keyEvent.preventDefault();

            let button = document.querySelector(`[data-draw-tool="${tool.id}"]`);
            if (button !== null) {
                button.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));
            }

            this.signal('hotkey:pressed', {
                tool: tool,
                event: keyEvent
            });
        } else if (keyEvent.key === 'Escape') {
            // Provide a way to cancel the current map state always
            getState().getDungeonMap().setMapState(null);
        }
    }

    /**
     * @param chord {String} E.g. `1`, `p` or `shift+u`
     * @returns {{key: String, shift: Boolean, ctrl: Boolean, alt: Boolean, meta: Boolean}}
     */
    static parseChord(chord) {
        let parts = chord.toLowerCase().split('+');
        // A chord on the plus key itself ends in an empty part
        let key = parts.pop() || '+';

        return {
            key: key,
            shift: parts.includes('shift'),
            ctrl: parts.includes('ctrl'),
            alt: parts.includes('alt'),
            meta: parts.includes('meta'),
        };
    }

    /**
     * Modifiers must match exactly, so Ctrl+1 does not trigger a tool bound to `1`.
     * @param chord {String}
     * @param keyEvent {KeyboardEvent|jQuery.Event}
     * @returns {boolean}
     */
    static matchesChord(chord, keyEvent) {
        let parsed = Hotkeys.parseChord(chord);

        return parsed.key === keyEvent.key.toLowerCase() &&
            parsed.shift === !!keyEvent.shiftKey &&
            parsed.ctrl === !!keyEvent.ctrlKey &&
            parsed.alt === !!keyEvent.altKey &&
            parsed.meta === !!keyEvent.metaKey;
    }

    /**
     * @param tools {DrawTool[]}
     * @param keyEvent {KeyboardEvent|jQuery.Event}
     * @returns {DrawTool|null}
     */
    static findToolForKeyEvent(tools, keyEvent) {
        return tools.find((tool) => (tool.keys ?? []).some((chord) => Hotkeys.matchesChord(chord, keyEvent))) ?? null;
    }

    /**
     * @param chord {String}
     * @returns {String} E.g. `Shift+U`
     */
    static formatChord(chord) {
        let parsed = Hotkeys.parseChord(chord);
        let parts = [];
        for (let modifier of ['ctrl', 'alt', 'meta', 'shift']) {
            if (parsed[modifier]) {
                parts.push(modifier.charAt(0).toUpperCase() + modifier.slice(1));
            }
        }
        parts.push(parsed.key.toUpperCase());

        return parts.join('+');
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {Hotkeys};
}
