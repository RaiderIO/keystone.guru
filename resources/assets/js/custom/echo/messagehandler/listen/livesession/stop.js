class StopHandler extends MessageHandler {

    constructor(echo) {
        super(echo, '.livesession-stop');
    }


    /**
     *
     * @param e {LiveSessionStopMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        // Make sure that we set the seconds in which it expires - otherwise it spawns at 0
        getState().getMapContext().setExpiresInSeconds(e.expires_in);

        /** @type {DungeonrouteLivesession} */
        let code = _inlineManager.getInlineCode('dungeonroute/livesession');
        code.startExpiresCountdown();

        // Pop up the modal so that everyone knows it's stopped
        $('#stop_live_session_modal').modal('show');
    }
}