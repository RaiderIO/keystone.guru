/**
 * Applies every live session state broadcast, including the ones this user's own actions caused: the state is the
 * server's, not an echo of a local change.
 */
class LiveSessionStateHandler extends MessageHandler {

    constructor(echo) {
        super(echo, LiveSessionStateMessage.getName());
    }

    /**
     * @param e {LiveSessionStateMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        let liveSessionStateManager = this.echo.map.liveSessionStateManager ?? null;
        if (liveSessionStateManager !== null) {
            liveSessionStateManager.setState(e.state);
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LiveSessionStateHandler,
    };
}
