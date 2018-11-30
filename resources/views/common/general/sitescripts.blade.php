<?php
$showLegalModal = isset($showLegalModal) ? $showLegalModal : true;
?>
<script>

    let _legalStartTimer = new Date().getTime();

    document.addEventListener("DOMContentLoaded", function (event) {
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
    });

    /**
     * Handler for when the 'I agree' button has been pressed for a returning user.
     **/
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
</script>
