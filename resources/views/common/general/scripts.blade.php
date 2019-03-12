<?php
$showLegalModal = isset($showLegalModal) ? $showLegalModal : true;
?>
<script>

    /** Instance that handles all inline code for specific pages */
    var _inlineManager;

    document.addEventListener("DOMContentLoaded", function (event) {
        _inlineManager = new InlineManager();
        // Default error handler
        $.ajaxSetup({
            error: defaultAjaxErrorFn
        });

        // Fade out success messages. They're not too interesting
        $("#app_session_status_message").delay(7000).fadeOut(200);

        // Enable tooltips for all elements
        refreshTooltips();

        // Make sure selectpicker is enabled
        $(".selectpicker").selectpicker();

        $('#import_string_textarea').bind('paste', _importStringPasted);

        @guest
        newPassword('#register_password');
        newPassword('#modal-register_password');
        @endguest
    });

    /**
     * Initiates a password checker on a 'enter your password' input.
     **/
    function newPassword(selector) {
        var $selector = $(selector);
        if ($selector.length > 0) {
            $selector.password({
                enterPass: '&nbsp;',
                shortPass: '{{ __('Minimum password length is 8') }}',
                badPass: '{{ __('Weak') }}',
                goodPass: '{{ __('Medium') }}',
                strongPass: '{{ __('Strong') }}',
                containsUsername: '{{ __('Password cannot contain your username') }}',
                showText: true, // shows the text tips
                animate: false, // whether or not to animate the progress bar on input blur/focus
                minimumLength: 8
            });
        }
    }

    /**
     * Called whenever the MDT import string has been pasted into the text area.
     **/
    function _importStringPasted(typedEvent) {
        // https://stackoverflow.com/questions/686995/catch-paste-input
        var $importString = $('#import_string_textarea');

        // Ugly, but needed since otherwise the field would be disabled prior to the value being actually assigned
        setTimeout(function () {
            // Can no longer edit it
            $importString.prop('disabled', true);
        }, 10);

        $.ajax({
            type: 'POST',
            url: '{{ route('mdt.details') }}',
            dataType: 'json',
            data: {
                'import_string': typedEvent.originalEvent.clipboardData.getData('text')
            },
            beforeSend: function () {
                $('#import_string_loader').show();
            },
            complete: function () {
                $('#import_string_loader').hide();
            },
            success: function (responseData) {
                var detailsTemplate = Handlebars.templates['import_string_details_template'];

                var data = $.extend({
                    details: [
                        {key: "{{ __('Faction') }}", value: responseData.faction},
                        {key: "{{ __('Dungeon') }}", value: responseData.dungeon},
                        {key: "{{ __('Affixes') }}", value: responseData.affixes.join('<br>')},
                        {key: "{{ __('Pulls') }}", value: responseData.pulls},
                        {key: "{{ __('Drawn lines') }}", value: responseData.lines},
                        {key: "{{ __('Notes') }}", value: responseData.notes},
                        {
                            key: "{{ __('Enemy forces') }}",
                            value: responseData.enemy_forces + '/' + responseData.enemy_forces_max
                        }
                    ]
                }, getHandlebarsTranslations());

                // Build the preview from the template
                $("#import_string_details").html(detailsTemplate(data));

                // Inject the warnings, if there are any
                if (responseData.warnings.length > 0) {
                    var warningsTemplate = Handlebars.templates['import_string_warnings_template'];

                    var warningsData = $.extend({
                        warnings: []
                    }, getHandlebarsTranslations());

                    // construct the handlebars data
                    for (var i = 0; i < responseData.warnings.length; i++) {
                        var warning = responseData.warnings[i];

                        warningsData.warnings.push({
                            category: warning.category,
                            message: warning.message,
                            details: warning.data.details
                        });
                    }

                    // Assign the template data to the div
                    $("#import_string_warnings").html(warningsTemplate(warningsData));
                }

                // Tooltips may be added above
                refreshTooltips();

                $('#import_string').val($importString.val());
                $('#mdt_import_modal input[type="submit"]').prop('disabled', false);
            }, error: function (xhr, textStatus, errorThrown) {
                $importString.removeProp('disabled');

                $("#import_string_details").html('');
                $("#import_string_warnings").html('');

                $('#mdt_import_modal input[type="submit"]').prop('disabled', true);
                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    /**
     * The default function that should be called when an ajax request fails (error handler)
     **/
    function defaultAjaxErrorFn(xhr, textStatus, errorThrown) {
        var message = "{{ __('An error occurred while performing your request. Please try again.') }}";

        switch (xhr.status) {
            case 403:
                message = "{{ __('You are not authorized to perform this request.') }}";
                break;
            case 404:
                message = "{{ __('The requested resource was not found.') }}";
                break;
            case 419:
                message = "{{ __('Your session has expired. Refresh the page.') }}";
                break;
        }

        // If json was set
        if (typeof xhr.responseJSON === 'object') {
            // There were Laravel errors
            if (typeof xhr.responseJSON.errors === 'object') {
                var errors = xhr.responseJSON.errors;
                message = '';
                // Extract them and put them in the response string.
                for (var key in errors) {
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
     * Refreshes fancy tooltips on all elements that request for them.
     */
    function refreshTooltips() {
        // Do not do tooltips on touch enabled devices, they tend to bug out
        //if (false === ("ontouchstart" in window || window.DocumentTouch && document instanceof DocumentTouch)) {
        if (!isMobile()) {
            $('[data-toggle="tooltip"]').tooltip('_fixTitle').tooltip();
        }
        // $('[data-toggle="tooltip"]').tooltip({trigger: 'manual'}).tooltip('show');
    }

    /**
     * Refreshes all select pickers on-screen
     **/
    function refreshSelectPickers() {
        var $selectpicker = $('.selectpicker');
        $selectpicker.selectpicker('refresh');
        $selectpicker.selectpicker('render');
    }

    /**
     * Checks if the current user is on a mobile device or not.
     **/
    function isMobile() {
        return {{ (new \Jenssegers\Agent\Agent())->isMobile() ? 'true' : 'false' }};
    }

    /**
     * Add a fixed message to a container with an arbitrary type.
     * @param containerSelector
     * @param type
     * @param message
     * @param durationMs
     * @param small
     * @private
     */
    function _addFixedMessage(containerSelector, type, message, durationMs, small = false) {
        var template = Handlebars.templates['app_fixed_message_' + (small ? 'small_' : '') + 'template'];

        var handlebarsData = {
            type: type,
            message: message
        };

        var $message = $(template(handlebarsData));
        $(containerSelector).append($message);

        $message.delay(durationMs).fadeOut(200, function () {
            $(this).remove();
        });

        return $message;
    }

    /**
     * Add a fixed footer message with an arbitrary type.
     * @param type
     * @param message
     * @param durationMs
     * @param small
     * @private
     */
    function _addFixedFooter(type, message, durationMs, small = false) {
        return _addFixedMessage('#fixed_footer_container', type, message, durationMs, small);
    }

    /**
     * Add a fixed header with an arbitrary type.
     * @param type
     * @param message
     * @param durationMs
     * @param small
     * @private
     */
    function _addFixedHeader(type, message, durationMs, small = false) {
        return _addFixedMessage('#fixed_header_container', type, message, durationMs, small);
    }

    /**
     * Add a small fixed header which only wraps the content in a background.
     * @returns The created header element.
     **/
    function addFixedHeaderSmall(message, durationMs = 5000) {
        return _addFixedHeader('info', '<i class="fas fa-info-circle"></i> ' + message, durationMs, true);
    }

    /**
     * Add a small fixed footer which only wraps the content in a background.
     * @returns The created footer element.
     **/
    function addFixedFooterSmall(message, durationMs = 5000) {
        return _addFixedFooter('info', '<i class="fas fa-info-circle"></i> ' + message, durationMs, true);
    }

    /**
     * Adds a fixed info footer with a message and a duration to the bottom of the screen.
     * @returns The created footer element.
     */
    function addFixedFooterInfo(message, durationMs = 5000) {
        return _addFixedFooter('info', '<i class="fas fa-info-circle"></i> ' + message, durationMs);
    }

    /**
     * Adds a fixed success footer with a message and a duration to the bottom of the screen.
     * @returns The created footer element.
     */
    function addFixedFooterSuccess(message, durationMs = 5000) {
        return _addFixedFooter('success', '<i class="fas fa-check-circle"></i> ' + message, durationMs);
    }

    /**
     * Adds a fixed warning footer with a message and a duration to the bottom of the screen.
     * @returns The created footer element.
     */
    function addFixedFooterWarning(message, durationMs = 5000) {
        return _addFixedFooter('warning', '<i class="fas fa-exclamation-triangle"></i> ' + message, durationMs);
    }

    /**
     * Adds a fixed error footer with a message and a duration to the bottom of the screen.
     * @returns The created footer element.
     */
    function addFixedFooterError(message, durationMs = 5000) {
        return _addFixedFooter('danger', '<i class="fas fa-times-circle"></i> ' + message, durationMs);
    }
</script>
