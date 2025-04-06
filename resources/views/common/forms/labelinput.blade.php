<?php
/**
 * @var string       $name
 * @var string       $label
 * @var string|null  $title
 * @var boolean|null $hidden
 */

$id    = $id ?? null;
$title = $title ?? null;
$hidden = $hidden ?? false;
?>
<div @if($id !== null) id="{{ $id }}" @endif class="form-group" @if($hidden) style="display: none;" @endif >
    <div class="row">
        <div class="col">
            <label for="{{ $name }}">
                {{ $label }}
            </label>
        </div>
        @if( $title !== null )
            <div class="col-auto">
                <i class="fas fa-info-circle pr-2" data-toggle="tooltip" title="{{$title}}"></i>
            </div>
        @endif
    </div>
    <div class="row">
        <div class="col">
            {{ $slot }}
        </div>
    </div>
</div>
