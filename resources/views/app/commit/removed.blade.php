@if(!empty($removed))
**{{ __('views/app.commit.removed.removed') }}**:
    @foreach($removed as $file)
- {{ $file }}
    @endforeach
@endif