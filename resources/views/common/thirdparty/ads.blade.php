@if(config('app.type') === 'live')
    {{--@include('common.thirdparty.nitropay.nitropay')--}}
    {{--@include('common.thirdparty.adsense')--}}
    @include('common.thirdparty.playwire.playwire')
@else
@endif
