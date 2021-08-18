@if(!empty($commit['removed']))
    **{{ __('views/app.commit.removed.removed') }}**:
    @foreach($commit['removed'] as $removed)
        - {{ $removed }}
    @endforeach
@endif