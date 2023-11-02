@if(config('app.type') === 'production')
    @include('common.thirdparty.nitropay.nitropay')
    {{--@include('common.thirdparty.adsense')--}}
@else
    @include('common.thirdparty.playwire.playwire')
@endif
