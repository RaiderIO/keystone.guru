<?php
$class  ??= '';
$size   ??= 'md';
$static ??= false;
$active ??= false;
$showClose ??= true;
?>
@if( $active )
    @include('common.general.inline', ['path' => 'modal/active', 'options' => [
        'id' => '#' . $id
    ]])
@endif

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true" data-keyboard="false"
     @if($static)
         data-backdrop="static"
    @endif>
    <div class="{{ $class }} modal-dialog modal-{{$size}} vertical-align-center">
        <div class="modal-content">
            @if($showClose)
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                    <i class="fas fa-times"></i>
                </button>
            @endif
            <div class="probootstrap-modal-flex">
                <div class="probootstrap-modal-content">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
