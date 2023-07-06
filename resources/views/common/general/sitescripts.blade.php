<?php
$showLegalModal = isset($showLegalModal) ? $showLegalModal : true;
?>

<script>
    /** Token that should be included in all forms to prevent cross-site request forgery */
    let csrfToken = "{{ csrf_token() }}";

    var isUserAdmin = {{ Auth::check() && Auth::user()->hasRole('admin') ? 'true' : 'false' }};

    var _legalStartTimer = new Date().getTime();
    @auth
    // Legal nag so that everyone agrees to the terms, that has registered.
    @if($showLegalModal && !Auth::user()->legal_agreed)

    document.addEventListener('DOMContentLoaded', function (event) {
        $('#legal_modal').modal('show');
        $('#legal_confirm_btn').unbind('click').bind('click', _agreeLegalBtnClicked);
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

    @endif
    @endauth

    {{--document.addEventListener('DOMContentLoaded', function (event) {--}}
    {{--    setInterval(function () {--}}
    {{--        var csrfToken = $('[name="csrf-token"]').val();--}}
    {{--        $.ajax({--}}
    {{--            url: '{{ route('api.refresh_csrf') }}',--}}
    {{--            type: 'get'--}}
    {{--        }).done(function (data) {--}}
    {{--            _setCsrfToken(data.token);--}}
    {{--        }).fail(function () {--}}
    {{--            console.error('Unable to refresh session! Site will probably not work anymore now..');--}}
    {{--        });--}}
    {{--    }, {{ config('session.lifetime') * 60000 }});--}}
    {{--});--}}

    /**
     *
     * @param newCsrfToken
     * @private
     */
    function _setCsrfToken(newCsrfToken) {
        $('[name="csrf-token"]').attr('content', newCsrfToken);
        csrfToken = newCsrfToken;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': newCsrfToken
            }
        });
    }
</script>
