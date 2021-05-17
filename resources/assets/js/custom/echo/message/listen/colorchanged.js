class ColorChanged extends MessageHandler {

    constructor(echo) {
        super(echo, '.user-color-changed');
    }


    onReceive(e) {
        super.onReceive(e);

        this.echo.setUserColorById(e.user.id, e.color);
    }
}