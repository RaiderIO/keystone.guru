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