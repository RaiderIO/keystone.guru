@if ($errors->has($key))
<span class="help-block">
    <strong>{{ $errors->first($key) }}</strong>
</span>
@endif