<script>

    document.addEventListener("DOMContentLoaded", function (event) {
        // Default error handler
        $.ajaxSetup({
            error: function (xhr, textStatus, errorThrown) {
                let message = "{{ __('An error occurred while performing your request. Please try again.') }}";

                // If json was set
                if (typeof xhr.responseJSON === 'object') {
                    // There were Laravel errors
                    if (typeof xhr.responseJSON.errors === 'object') {
                        let errors = xhr.responseJSON.errors
                        message = '';
                        // Extract them and put them in the response string.
                        for (let key in errors) {
                            if (errors.hasOwnProperty(key)) {
                                message += errors[key] + ' ';
                            }
                        }
                    }
                }
                addFixedFooterError(message + " (" + xhr.status + ")");
            }
        });

        // Fade out success messages. They're not too interesting
        $("#app_session_status_message").delay(7000).fadeOut(200);
    });

    /**
     * Refreshes fancy tooltips on all elements that request for them.
     */
    function refreshTooltips() {
        $('[data-toggle="tooltip"]').tooltip();
        // $('[data-toggle="tooltip"]').tooltip({trigger: 'manual'}).tooltip('show');
    }

    /**
     * Adds a fixed info footer with a message and a duration to the bottom of the screen.
     */
    function addFixedFooterInfo(message, durationMs = 5000) {
        _addFixedFooter('info', '<i class="fas fa-info-circle"></i> ' + message, durationMs);
    }

    /**
     * Adds a fixed success footer with a message and a duration to the bottom of the screen.
     */
    function addFixedFooterSuccess(message, durationMs = 5000) {
        _addFixedFooter('success', '<i class="fas fa-check-circle"></i> ' + message, durationMs);
    }

    /**
     * Adds a fixed warning footer with a message and a duration to the bottom of the screen.
     */
    function addFixedFooterWarning(message, durationMs = 5000) {
        _addFixedFooter('warning', '<i class="fas fa-exclamation-triangle"></i> ' + message, durationMs);
    }

    /**
     * Adds a fixed error footer with a message and a duration to the bottom of the screen.
     */
    function addFixedFooterError(message, durationMs = 5000) {
        _addFixedFooter('danger', '<i class="fas fa-times-circle"></i> ' + message, durationMs);
    }

    /**
     * Add a fixed footer with an arbitrary type.
     * @param type
     * @param message
     * @param durationMs
     * @private
     */
    function _addFixedFooter(type, message, durationMs) {
        let fixedFooterTemplate = $('#app_fixed_footer_template').html();

        let template = handlebars.compile(fixedFooterTemplate);

        let handlebarsData = {
            type: type,
            message: message
        };

        let $message = $(template(handlebarsData));
        $('#fixed_footer_container').append($message);

        $message.delay(durationMs).fadeOut(200, function () {
            $(this).remove();
        });
    }
</script>
