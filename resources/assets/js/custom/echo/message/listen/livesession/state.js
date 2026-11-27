/**
 * @property {LiveSessionStateData} state
 */
class LiveSessionStateMessage extends Message {
    static getName() {
        return 'livesession-state';
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LiveSessionStateMessage,
    };
}
