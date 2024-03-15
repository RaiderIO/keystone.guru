<div class="form-group">
    {!! sprintf(__('view_common.modal.legal.welcome_back_agree'),
         sprintf('<a href="%s">%s</a>', route('legal.terms'), __('view_common.modal.legal.terms_of_service')),
         sprintf('<a href="%s">%s</a>', route('legal.privacy'), __('view_common.modal.legal.privacy_policy')),
         sprintf('<a href="%s">%s</a>', route('legal.cookies'), __('view_common.modal.legal.cookie_policy'))
     )
     !!}
</div>
<div id="legal_confirm_btn" class="btn btn-primary">
    {{ __('view_common.modal.legal.i_agree') }}
</div>
