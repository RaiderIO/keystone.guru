class StopHandler extends MessageHandler {

    constructor(echo) {
        super(echo, '.livesession-stop');
    }


    onReceive(e) {
        super.onReceive(e);


        /** @type {DungeonrouteLivesession} */
        let code = _inlineManager.getInlineCode('dungeonroute/livesession');
        code.startExpiresCountdown();

        // Pop up the modal so that everyone knows it's stopped
        $('#stop_live_session_modal').modal('show');
    }
}