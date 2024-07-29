@if(!empty($added))
    **{{ __('view_app.commit.added.added') }}**:
    @foreach($added as $file)
        + {{ $file }}
    @endforeach
@endif
