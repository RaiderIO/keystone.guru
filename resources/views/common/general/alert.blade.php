<?php
/**
 * @var string $name
 * @var string $type Can be warning, info, danger
 */
$name      ??= 'default';
$type      ??= '';
$dismiss   ??= true;
$rootClass ??= '';
$align     ??= 'start';
?>
@if(!isAlertDismissed($name))
    <div class="alert alert-{{ $type }} text-{{$align}} mt-4 {{ $dismiss ? 'alert-dismissible' : '' }} {{ $rootClass }}"
         role="alert">
        @if($dismiss)
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close" data-alert-dismiss-id="{{ $name }}"></button>
        @endif

        @if($type === 'info')
            <i class="fas fa-info-circle"></i>
        @elseif($type === 'warning')
            <i class="fa fa-exclamation-triangle"></i>
        @endif

        {{ $slot }}
    </div>
@endif
