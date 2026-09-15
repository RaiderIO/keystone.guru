<?php
$class  ??= '';
$size   ??= 'md';
$static ??= false;
$active ??= false;
$showClose ??= true;
// Opt-in per modal: most modals here are deliberately not ESC-dismissable (the legal modal must be
// answered), but a modal the user may have opened by mistake needs a way out (#4004)
$keyboard ??= false;
?>
@if( $active )
    @include('common.general.inline', ['path' => 'modal/active', 'options' => [
        'id' => '#' . $id
    ]])
@endif

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true" data-bs-keyboard="{{ $keyboard ? 'true' : 'false' }}"
     @if($static)
         data-bs-backdrop="static"
    @endif>
    <div class="{{ $class }} modal-dialog modal-{{$size}} vertical-align-center">
        <div class="modal-content">
            @if($showClose)
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            @endif
            <div class="probootstrap-modal-flex">
                <div class="probootstrap-modal-content">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
