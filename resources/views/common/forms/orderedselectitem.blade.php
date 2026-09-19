<?php
/**
 * One row of common.forms.orderedselect. Also rendered inside its <template>, which the script clones.
 *
 * @var string $name
 * @var int    $itemId
 * @var string $itemLabel
 * @var array{text: string, isWarning?: bool}|null $itemDetail
 * @var string|null $detailWarningText
 * @var int    $position
 */
$itemDetail        ??= null;
$detailWarningText ??= null;
$isDetailWarning   = (bool)($itemDetail['isWarning'] ?? false);
?>
<li class="list-group-item ordered_select_item d-flex align-items-center" data-id="{{ $itemId }}">
    <span class="ordered_select_handle" aria-hidden="true">
        <i class="fas fa-grip-vertical"></i>
    </span>
    <span class="ordered_select_position" aria-hidden="true">{{ $position }}</span>
    <span class="ordered_select_label flex-fill">{{ $itemLabel }}</span>
    <span class="ordered_select_detail{{ $isDetailWarning ? ' ordered_select_detail_warning' : '' }}"
          @if($itemDetail === null) hidden @endif
          @if($isDetailWarning && $detailWarningText !== null) title="{{ $detailWarningText }}" @endif>
        <i class="fas fa-exclamation-triangle ordered_select_detail_icon" aria-hidden="true" @if(!$isDetailWarning) hidden @endif></i>
        <span class="ordered_select_detail_text">{{ $itemDetail['text'] ?? '' }}</span>
        @if($detailWarningText !== null)
            <span class="visually-hidden ordered_select_detail_warning_text" @if(!$isDetailWarning) hidden @endif>{{ $detailWarningText }}</span>
        @endif
    </span>
    <button type="button" class="btn btn-sm ordered_select_up"
            aria-label="{{ __('view_common.forms.orderedselect.move_up', ['name' => $itemLabel]) }}">
        <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </button>
    <button type="button" class="btn btn-sm ordered_select_down"
            aria-label="{{ __('view_common.forms.orderedselect.move_down', ['name' => $itemLabel]) }}">
        <i class="fas fa-arrow-down" aria-hidden="true"></i>
    </button>
    <button type="button" class="btn btn-sm ordered_select_remove"
            aria-label="{{ __('view_common.forms.orderedselect.remove', ['name' => $itemLabel]) }}">
        <i class="fas fa-times" aria-hidden="true"></i>
    </button>
    <input type="hidden" name="{{ $name }}[]" value="{{ $itemId }}">
</li>
