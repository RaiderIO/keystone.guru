@if(config('app.type') === 'live' || config('app.type') === 'staging')
    {{--@include('common.thirdparty.nitropay.adunit')--}}
    {{--@include('common.thirdparty.adsense.adunit')--}}
    @include('common.thirdparty.playwire.adunit')
@else
@endif
