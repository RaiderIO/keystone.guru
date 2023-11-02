@if(config('app.type') === 'production')
    @include('common.thirdparty.nitropay.adunit')
    {{--@include('common.thirdparty.adsense.adunit')--}}
@else
    @include('common.thirdparty.playwire.adunit')
@endif
