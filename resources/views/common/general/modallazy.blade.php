<?php
/**
 * @var string $targetView
 */
$class     ??= '';
$size      ??= 'md';
$static    ??= false;
$active    ??= false;
$showClose ??= true;
?>
@include('common.general.inline', ['path' => 'modal/lazy', 'options' => [
    'id' => '#' . $id,
    'view' => $targetView,
]])
@component('common.general.modal', [
    'id' => $id,
    'class' => $class,
    'size' => $size,
    'static' => $static,
    'active' => $active,
    'showClose' => $showClose,
])
    <div class="text-center my-5">
        <i class="fas fa-spinner fa-pulse fa-3x"></i>
    </div>
@endcomponent
