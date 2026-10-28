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
            // Brushline
            case BrushlineChangedMessage.getName():
                result = new BrushlineChangedMessage(props);
                break;
            case BrushlineDeletedMessage.getName():
                result = new BrushlineDeletedMessage(props);
                break;
            // Arrow
            case ArrowChangedMessage.getName():
                result = new ArrowChangedMessage(props);
                break;
            case ArrowDeletedMessage.getName():
                result = new ArrowDeletedMessage(props);
                break;
            // KillZone
            case KillZoneChangedMessage.getName():
                result = new KillZoneChangedMessage(props);
                break;
            case KillZoneDeletedMessage.getName():
                result = new KillZoneDeletedMessage(props);
                break;
            // MapIcon
            case MapIconChangedMessage.getName():
                result = new MapIconChangedMessage(props);
                break;
            case MapIconDeletedMessage.getName():
                result = new MapIconDeletedMessage(props);
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
            // Path
            case PathChangedMessage.getName():
                result = new PathChangedMessage(props);
                break;
            case PathDeletedMessage.getName():
                result = new PathDeletedMessage(props);
                break;

            // Color changed
            case UserColorChangedMessage.getName():
                result = new UserColorChangedMessage(props);
                break;

            // LiveSession
            case LiveSessionInviteMessage.getName():
                result = new LiveSessionInviteMessage(props);
                break;
            case LiveSessionStopMessage.getName():
                result = new LiveSessionStopMessage(props);
                break;
            case EnemyKilledMessage.getName():
                result = new EnemyKilledMessage(props);
                break;
            case PlayerMovedMessage.getName():
                result = new PlayerMovedMessage(props);
                break;
            case RouteCorrectionMessage.getName():
                result = new RouteCorrectionMessage(props);
                break;
            case InCombatEnemiesChangedMessage.getName():
                result = new InCombatEnemiesChangedMessage(props);
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
