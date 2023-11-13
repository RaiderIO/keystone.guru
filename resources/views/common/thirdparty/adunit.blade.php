@if(config('app.type') === 'live')
    {{--@include('common.thirdparty.nitropay.adunit')--}}
    {{--@include('common.thirdparty.adsense.adunit')--}}
    @include('common.thirdparty.playwire.adunit')
@else
@endif
