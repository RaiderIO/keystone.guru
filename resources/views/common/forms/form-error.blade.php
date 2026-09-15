@if ($errors->has($key))
    {{-- d-block: consumers don't necessarily set .is-invalid on the input, which is what would normally show this --}}
    <div class="invalid-feedback d-block" role="alert">
        <strong>{{ $errors->first($key) }}</strong>
    </div>
@endif
