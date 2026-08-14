/**
 @typedef {Object} CommonFormsAuthformOptions
 @property {string} formSelector
 @property {string|null} successUrl URL to navigate to on success instead of reloading the current
 page. Set by the blade when the current page is a guest-only page (anything behind the `guest`
 middleware) which the now-authenticated user cannot stay on.
 */

/**
 * Submits a login/register form over AJAX so that validation failures render inside the modal the
 * form is in, instead of navigating to a full page and losing the user's page state (route being
 * edited, live session presence etc.).
 *
 * @property {CommonFormsAuthformOptions} options
 */
class CommonFormsAuthform extends InlineCode {

    activate() {
        super.activate();

        $(this.options.formSelector).unbind('submit').bind('submit', this._submit.bind(this));
    }

    /**
     * @param {Event} event
     * @private
     */
    _submit(event) {
        event.preventDefault();

        let $form = $(this.options.formSelector);

        // The register form's own submit handler also writes this, but handler order is not
        // guaranteed, so write it before serializing (defined in sitescripts.blade)
        let $legalAgreedMs = $form.find('input[name="legal_agreed_ms"]');
        if ($legalAgreedMs.length > 0 && typeof _legalStartTimer !== 'undefined') {
            $legalAgreedMs.val(new Date().getTime() - _legalStartTimer);
        }

        this._clearErrors($form);

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: $form.serialize(),
            // Make Laravel's expectsJson() return true so a ValidationException renders as a
            // 422 JSON response instead of a redirect (which would eject the user out of the modal)
            headers: {
                'Accept': 'application/json',
            },
            success: function () {
                this._navigate(this.options.successUrl);
            }.bind(this),
            error: this._onError.bind(this),
        });
    }

    /**
     * Navigates away after a successful login/registration.
     *
     * Without a successUrl we reload rather than following the response's redirect - the user
     * logged in from this page, so this page is where they want to be, now authenticated. That
     * breaks down when the modal was opened on a guest-only page (/login, /register, the password
     * reset flow): reloading it bounces off the `guest` middleware to the home page, and that extra
     * hop ages out the flashed "registered successfully" message before anything renders it. The
     * blade hands us the home page URL in that case so we get there in one hop with the flash
     * intact.
     *
     * @param {string|null} successUrl
     * @private
     */
    _navigate(successUrl) {
        if (typeof successUrl === 'string' && successUrl.length > 0) {
            window.location.href = successUrl;
        } else {
            window.location.reload();
        }
    }

    /**
     * @param {Object} xhr
     * @param {string} textStatus
     * @param {string} errorThrown
     * @private
     */
    _onError(xhr, textStatus, errorThrown) {
        if (xhr.status === 422 && typeof xhr.responseJSON === 'object' && typeof xhr.responseJSON.errors === 'object') {
            this._renderErrors($(this.options.formSelector), xhr.responseJSON.errors);
        } else {
            // Throttled (429), expired session (419), server error - fall back to the default toast
            defaultAjaxErrorFn(xhr, textStatus, errorThrown);
        }
    }

    /**
     * Renders Laravel's {errors: {field: [messages]}} 422 response as Bootstrap field-level errors.
     *
     * @param {jQuery} $form
     * @param {Object} errors
     * @private
     */
    _renderErrors($form, errors) {
        let unmatched = [];

        for (let key in errors) {
            if (!errors.hasOwnProperty(key)) {
                continue;
            }

            let messages = errors[key];
            let message = Array.isArray(messages) ? messages.join(' ') : messages;

            let $input = $form.find(`[name="${key}"]`).last();
            if ($input.length > 0) {
                $input.addClass('is-invalid').attr('aria-invalid', 'true');
                // d-block: the theme build prefixes Bootstrap's `.is-invalid ~ .invalid-feedback`
                // display rule under the theme class, which breaks it - show it explicitly
                $('<div class="invalid-feedback d-block" role="alert">').text(message).insertAfter($input);
            } else {
                unmatched.push(message);
            }
        }

        if (unmatched.length > 0) {
            showErrorNotification(unmatched.join(' '));
        }

        $form.find('.is-invalid').first().trigger('focus');
    }

    /**
     * @param {jQuery} $form
     * @private
     */
    _clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $form.find('.invalid-feedback').remove();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonFormsAuthform};
}
