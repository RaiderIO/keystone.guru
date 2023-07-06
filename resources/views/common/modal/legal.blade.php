<div class="form-group">
    {!! sprintf(__('views/common.modal.legal.welcome_back_agree'),
         sprintf('<a href="%s">%s</a>', route('legal.terms'), __('views/common.modal.legal.terms_of_service')),
         sprintf('<a href="%s">%s</a>', route('legal.privacy'), __('views/common.modal.legal.privacy_policy')),
         sprintf('<a href="%s">%s</a>', route('legal.cookies'), __('views/common.modal.legal.cookie_policy'))
     )
     !!}
</div>
<div id="legal_confirm_btn" class="btn btn-primary">
    {{ __('views/common.modal.legal.i_agree') }}
</div>