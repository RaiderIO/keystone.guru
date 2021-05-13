class DungeonrouteLivesession extends InlineCode {

    /**
     */
    activate(){

        window.Echo.join(getState().getMapContext().getEchoChannelName())
            .listen('.user-color-changed', (e) => {
                self._setUserColorById(e.user.id, e.color);
            });
    }

    cleanup() {

    }
}