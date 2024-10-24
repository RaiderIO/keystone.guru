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
            // KillZone
            case KillZoneChangedMessage.getName():
                result = new KillZoneChangedMessage(props);
                break;
            case KillZoneDeletedMessage.getName():
                result = new KillZoneDeletedMessage(props);
                break;

            // LiveSession
            case LiveSessionInviteMessage.getName():
                result = new LiveSessionInviteMessage(props);
                break;
            case LiveSessionStopMessage.getName():
                result = new LiveSessionStopMessage(props);
                break;

            // Npc
            case NpcChangedMessage.getName():
                result = new NpcChangedMessage(props);
                break;
            case NpcDeletedMessage.getName():
                result = new NpcDeletedMessage(props);
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
