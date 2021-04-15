@if(!empty($commit['modified']))
    **{{ __('Modified') }}**:
    @foreach($commit['modified'] as $modified)
          = {{ $modified }}
    @endforeach
@endif