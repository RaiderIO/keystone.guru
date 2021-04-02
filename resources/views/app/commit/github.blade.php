<?php /** @var $commit array */ ?>
<?php /** @var $lines array */ ?>
@if(!empty($lines) > 0)
    {{ implode('\n', $lines) }}

@endisset

@if(!empty($commit['added']))
    **{{ __('Added') }}**:
    @foreach($commit['added'] as $added)
        + {{ $added }}
    @endforeach
@endif

@if(!empty($commit['modified']))
    **{{ __('Modified') }}**:
    @foreach($commit['modified'] as $modified)
        {{ $modified }}
    @endforeach
@endif

@if(!empty($commit['removed']))
    **{{ __('Removed') }}**:
    @foreach($commit['removed'] as $removed)
        - {{ $removed }}
    @endforeach
@endif