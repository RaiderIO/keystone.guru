@if(!empty($modified))
**{{ __('view_app.commit.modified.modified') }}**:
    @foreach($modified as $file)
= {{ $file }}
    @endforeach
@endif
