<?php
$class = isset($class) ? $class : '';
?>
<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true" data-keyboard="false"
     data-backdrop="static">
    <div class="{{ $class }} modal-dialog modal-md vertical-align-center">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                <i class="fas fa-times"></i>
            </button>
            <div class="probootstrap-modal-flex">
                <div class="probootstrap-modal-content">
                    @yield('modal-content')
                </div>
            </div>
        </div>
    </div>
</div>