@if(config('app.type') === 'production' || config('app.type') === 'staging')
    {{--@include('common.thirdparty.nitropay.nitropay')--}}
    {{--@include('common.thirdparty.adsense')--}}
    @include('common.thirdparty.playwire.playwire')
@else
@endif
