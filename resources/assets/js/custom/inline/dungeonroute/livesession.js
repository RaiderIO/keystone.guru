class DungeonrouteLivesession extends InlineCode {

    /**
     */
    activate() {

        this.stopLiveSessionInterval = null;

        let mapContext = getState().getMapContext();

        // If this session is soon expiring..
        if (mapContext.getExpiresInSeconds() !== null) {
            this.startExpiresCountdown();
        }

        $('#stop_live_session').bind('click', this._stopLiveSession.bind(this));
    }

    /**
     * Starts the expires countdown, making the UI element count down
     */
    startExpiresCountdown() {
        if (this.stopLiveSessionInterval !== null) {
            clearInterval(this.stopLiveSessionInterval);
        }

        // Toggle UI state
        $('#stop_live_session').hide();
        $('#stopped_live_session_container').css('display', 'inherit');

        let tick = function () {
            let minutesRemaining = Math.floor(getState().getMapContext().getExpiresInSeconds() / 60);

            $('#stopped_live_session_countdown').html(
                `Expires in ${minutesRemaining === 0 ? '<1' : minutesRemaining}m`
            );

            // Subtract a second
            getState().getMapContext().setExpiresInSeconds(getState().getMapContext().getExpiresInSeconds() - 1);
        };

        tick();
        this.stopLiveSessionInterval = setInterval(tick, 1000);
    }

    /**
     *
     * @private
     */
    _stopLiveSession() {
        let self = this;
        let mapContext = getState().getMapContext();

        $.ajax({
            type: 'DELETE',
            url: `/ajax/${mapContext.getPublicKey()}/live/${mapContext.getLiveSessionPublicKey()}`,
            dataType: 'json',
            success: function (json) {
                getState().getMapContext().setExpiresInSeconds(parseInt(json.expires_in));
                self.startExpiresCountdown();
            }
        });
    }

    cleanup() {

    }
}