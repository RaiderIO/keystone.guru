@if(!empty($commit['modified']))
    **{{ __('views/app.commit.modified.modified') }}**:
    @foreach($commit['modified'] as $modified)
          = {{ $modified }}
    @endforeach
@endif