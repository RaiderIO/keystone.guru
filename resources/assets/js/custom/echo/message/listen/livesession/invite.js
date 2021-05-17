class Invite extends MessageHandler {

    constructor(echo) {
        super(echo, '.livesession-invite');
    }


    onReceive(e) {
        super.onReceive(e);

        // Only if we're invited!
        if (e.invitees.includes(getState().getMapContext().getUserId())) {
            let template = Handlebars.templates['livesession_invite_received_template'];

            showConfirmYesCancel(template($.extend({}, getHandlebarsDefaultVariables(), e)), function () {
                // If confirmed, redirect the user
                window.location.href = e.url;
            }, null, {closeWith: ['button']});
        }
    }
}