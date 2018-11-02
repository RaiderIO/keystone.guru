<?php
$showLegalModal = isset($showLegalModal) ? $showLegalModal : true;
?>
<script>

    let _legalStartTimer = new Date().getTime();

    document.addEventListener("DOMContentLoaded", function (event) {
        // Default error handler
        $.ajaxSetup({
            error: defaultAjaxErrorFn
        });

        // Fade out success messages. They're not too interesting
        $("#app_session_status_message").delay(7000).fadeOut(200);
        @guest
        newPassword('#register_password');
        newPassword('#modal-register_password');
        @endguest
        @auth
        // Legal nag so that everyone agrees to the terms, that has registered.
        @if($showLegalModal && !Auth::user()->legal_agreed)
        $('#legal_modal').modal('show');
        $('#legal_confirm_btn').bind('click', _agreeLegalBtnClicked);
        @endif
        @endauth

        // Enable tooltips for all elements
        refreshTooltips();

        // Make sure selectpicker is enabled
        $(".selectpicker").selectpicker();
    });

    function _agreeLegalBtnClicked() {
        $.ajax({
            type: 'POST',
            url: '/ajax/profile/legal',
            dataType: 'json',
            data: {
                time: new Date().getTime() - _legalStartTimer
            },
            beforeSend: function () {
                $('#legal_confirm_btn').attr('disabled', 'disabled');
            },
            success: function () {
                $('#legal_modal').modal('hide');
            },
            complete: function () {
                $('#legal_confirm_btn').removeAttr('disabled');
            }
        });
    }

    /**
     * Initiates a password checker on a 'enter your password' input.
     **/
    function newPassword(selector) {
        $(selector).password({
            enterPass: '&nbsp;',
            shortPass: '{{ __('Minimum password length is 8') }}',
            badPass: '{{ __('Weak') }}',
            goodPass: '{{ __('Medium') }}',
            strongPass: '{{ __('Strong') }}',
            containsUsername: '{{ __('Password cannot contain your username') }}',
            showText: true, // shows the text tips
            animate: false, // whether or not to animate the progress bar on input blur/focus
            minimumLength: 8
        })
    }

    /**
     * The default function that should be called when an ajax request fails (error handler)
     **/
    function defaultAjaxErrorFn(xhr, textStatus, errorThrown) {
        let message = "{{ __('An error occurred while performing your request. Please try again.') }}";

        switch (xhr.status) {
            case 403:
                message = "{{ __('You are not authorized to perform this request.') }}";
                break;
            case 404:
                message = "{{ __('The requested resource was not found.') }}";
                break;
        }

        // If json was set
        if (typeof xhr.responseJSON === 'object') {
            // There were Laravel errors
            if (typeof xhr.responseJSON.errors === 'object') {
                let errors = xhr.responseJSON.errors;
                message = '';
                // Extract them and put them in the response string.
                for (let key in errors) {
                    if (errors.hasOwnProperty(key)) {
                        message += errors[key] + ' ';
                    }
                }
            } else if (typeof xhr.responseJSON.message === 'string') {
                if (xhr.responseJSON.message.length > 0) {
                    message = xhr.responseJSON.message;
                }
            }
        }

        addFixedFooterError(message + " (" + xhr.status + ")");
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

    /**
     * Refreshes fancy tooltips on all elements that request for them.
     */
    function refreshTooltips() {
        $('[data-toggle="tooltip"]').tooltip('_fixTitle').tooltip();
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
</script>
