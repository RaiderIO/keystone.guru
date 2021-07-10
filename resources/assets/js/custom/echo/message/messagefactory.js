class MessageFactory {

    /**
     *
     * @param name {string}
     * @param props {Object}
     * @returns {Message|null}
     */
    create(name, props) {
        let result = null;
        switch (name) {
            // LiveSession
            case LiveSessionInviteMessage.getName():
                result = new LiveSessionInviteMessage(props);
                break;
            case LiveSessionStopMessage.getName():
                result = new LiveSessionStopMessage(props);
                break;

            // Overpulled enemies
            case OverpulledEnemyChangedMessage.getName():
                result = new OverpulledEnemyChangedMessage(props);
                break;
            case OverpulledEnemyDeletedMessage.getName():
                result = new OverpulledEnemyDeletedMessage(props);
                break;


            // Whisper
            case MousePositionMessage.getName():
                result = new MousePositionMessage(props);
                break;
            case ViewPortMessage.getName():
                result = new ViewPortMessage(props);
                break;
            default:
                console.error(`Unable to create Message from factory '${name}'`, props);
        }

        return result;
    }
}