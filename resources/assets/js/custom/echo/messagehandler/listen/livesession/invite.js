class InviteHandler extends MessageHandler {

    constructor(echo) {
        super(echo, LiveSessionInviteMessage.getName());
    }

    /**
     *
     * @param e {LiveSessionInviteMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        // Only if we're invited!
        if (e.invitees.includes(getState().getMapContext().getUserPublicKey())) {
            let template = Handlebars.templates['livesession_invite_received_template'];

            showConfirmYesCancel(template($.extend({}, getHandlebarsDefaultVariables(), e)), function () {
                // If confirmed, redirect the user
                window.location.href = e.url;
            }, null, {closeWith: ['button']});
        }
    }
}