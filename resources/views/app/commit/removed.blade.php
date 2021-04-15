@if(!empty($commit['removed']))
    **{{ __('Removed') }}**:
    @foreach($commit['removed'] as $removed)
        - {{ $removed }}
    @endforeach
@endif