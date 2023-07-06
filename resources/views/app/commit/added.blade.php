@if(!empty($added))
**{{ __('views/app.commit.added.added') }}**:
    @foreach($added as $file)
+ {{ $file }}
    @endforeach
@endif