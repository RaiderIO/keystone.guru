<div class="form-group">
    {!! sprintf(__('Welcome back! In order to proceed, you have to agree to our %s, %s and %s.'),
     '<a href="' . route('legal.terms') . '">terms of service</a>',
     '<a href="' . route('legal.privacy') . '">privacy policy</a>',
     '<a href="' . route('legal.cookies') . '">cookie policy</a>')
     !!}
</div>
<div id="legal_confirm_btn" class="btn btn-primary">
    {{ __('I agree') }}
</div>