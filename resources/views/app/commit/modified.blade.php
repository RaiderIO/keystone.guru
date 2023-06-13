@if(!empty($modified))
**{{ __('views/app.commit.modified.modified') }}**:
    @foreach($modified as $file)
= {{ $file }}
    @endforeach
@endif
