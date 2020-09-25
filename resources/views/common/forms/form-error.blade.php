@if ($errors->has($key))
<span class="help-block text-danger">
    <strong>{{ $errors->first($key) }}</strong>
</span>
@endif