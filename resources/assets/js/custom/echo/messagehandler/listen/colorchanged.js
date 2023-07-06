class ColorChangedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, '.user-color-changed');
    }


    onReceive(e) {
        super.onReceive(e);

        this.echo.setUserColorByPublicKey(e.user.public_key, e.color);
    }
}