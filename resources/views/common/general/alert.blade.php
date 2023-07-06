<?php
/** @var $name string */
/** @var $type string Can be warning, info, danger */
$name      = $name ?? 'default';
$type      = $type ?? '';
$dismiss   = $dismiss ?? true;
$rootClass = $rootClass ?? '';
$align = $align ?? 'left';
?>
@if(!isAlertDismissed($name))
    <div class="alert alert-{{ $type }} text-{{$align}} mt-4 {{ $dismiss ? 'alert-dismissable' : '' }} {{ $rootClass }}"
         role="alert">
        @if($dismiss)
            <a href="#" class="close" data-dismiss="alert" aria-label="close" data-alert-dismiss-id="{{ $name }}">
                <i class="fas fa-times"></i>
            </a>
        @endif

        @if($type === 'info')
            <i class="fas fa-info-circle"></i>
        @elseif($type === 'warning')
            <i class="fa fa-exclamation-triangle"></i>
        @endif

        {{ $slot }}
    </div>
@endif
