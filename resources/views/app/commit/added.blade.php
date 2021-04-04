@if(!empty($commit['added']))
    **{{ __('Added') }}**:
    @foreach($commit['added'] as $added)
        + {{ $added }}
    @endforeach
@endif