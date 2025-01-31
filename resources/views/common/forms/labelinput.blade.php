<?php
/**
 * @var string      $name
 * @var string      $label
 * @var string|null $title
 */

$id    = $id ?? null;
$title = $title ?? null;
?>
<div @if($id !== null) id="{{ $id }}" @endif class="form-group">
    <div class="row">
        <div class="col">
            <label for="{{ $name }}">
                {{ $label }}
            </label>
        </div>
        @if( $title !== null )
            <div class="col-auto">
                <i class="fas fa-info-circle pr-2" data-toggle="tooltip" title="{{$title}}"
                   style="padding-top: 0.75rem !important;"></i>
            </div>
        @endif
    </div>
    <div class="row">
        <div class="col">
            {{ $slot }}
        </div>
    </div>
</div>
