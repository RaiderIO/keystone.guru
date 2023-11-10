@if(config('app.type') === 'live')
{{--    @include('common.thirdparty.nitropay.nitropay')--}}
    {{--@include('common.thirdparty.adsense')--}}
@else
    @include('common.thirdparty.playwire.footer')
@endif
