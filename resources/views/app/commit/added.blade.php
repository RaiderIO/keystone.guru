@if(!empty($commit['added']))
    **{{ __('views/app.commit.added.added') }}**:
    @foreach($commit['added'] as $added)
        + {{ $added }}
    @endforeach
@endif