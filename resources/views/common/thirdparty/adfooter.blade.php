@if(config('app.type') === 'live' || config('app.type') === 'staging')
    {{--    @include('common.thirdparty.nitropay.nitropay')--}}
    {{--@include('common.thirdparty.adsense')--}}
    @include('common.thirdparty.playwire.footer')
@else
@endif
