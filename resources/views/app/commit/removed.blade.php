@if(!empty($removed))
**{{ __('view_app.commit.removed.removed') }}**:
    @foreach($removed as $file)
- {{ $file }}
    @endforeach
@endif
