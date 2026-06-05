/**
 * @typedef {Object} DungeonrouteLivesessionOptions
 * @property {string} stopLiveSessionSelector
 * @property {string} stoppedContainerSelector
 * @property {string} countdownSelector
 */

/**
 * @property {DungeonrouteLivesessionOptions} options
 */
class DungeonrouteLivesession extends InlineCode {

    activate() {

        this.stopLiveSessionInterval = null;

        let mapContext = getState().getMapContext();

        // If this session is soon expiring..
        if (mapContext.getExpiresInSeconds() !== null) {
            this.startExpiresCountdown();
        }

        $(this.options.stopLiveSessionSelector).unbind('click').bind('click', this._stopLiveSession.bind(this));
    }

    /**
     * Starts the expires countdown, making the UI element count down
     */
    startExpiresCountdown() {
        if (this.stopLiveSessionInterval !== null) {
            clearInterval(this.stopLiveSessionInterval);
        }

        // Toggle UI state
        $(this.options.stopLiveSessionSelector).hide();
        $(this.options.stoppedContainerSelector).css('display', 'inherit');

        let self = this;
        let tick = function () {
            let minutesRemaining = Math.floor(getState().getMapContext().getExpiresInSeconds() / 60);

            $(self.options.countdownSelector).html(
                `Expires in ${minutesRemaining === 0 ? '<1' : minutesRemaining}m`
            );

            // Subtract a second
            getState().getMapContext().setExpiresInSeconds(getState().getMapContext().getExpiresInSeconds() - 1);
        };

        tick();
        this.stopLiveSessionInterval = setInterval(tick, 1000);
    }

    /**
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
