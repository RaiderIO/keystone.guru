<?php
/**
 * @var string      $key     The validation key.
 * @var string|null $errorId Lets the input point at the message with aria-describedby.
 */
$errorId ??= null;
?>
@if ($errors->has($key))
    {{-- d-block: consumers don't necessarily set .is-invalid on the input, which is what would normally show this --}}
    <div @if($errorId !== null) id="{{ $errorId }}" @endif class="invalid-feedback d-block" role="alert">
        <strong>{{ $errors->first($key) }}</strong>
    </div>
@endif
