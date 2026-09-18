<?php
/**
 * One row of common.forms.orderedselect. Also rendered inside its <template>, which the script clones.
 *
 * @var string $name
 * @var int    $itemId
 * @var string $itemLabel
 * @var int    $position
 */
?>
<li class="list-group-item ordered_select_item d-flex align-items-center" data-id="{{ $itemId }}">
    <span class="ordered_select_handle" aria-hidden="true">
        <i class="fas fa-grip-vertical"></i>
    </span>
    <span class="ordered_select_position" aria-hidden="true">{{ $position }}</span>
    <span class="ordered_select_label flex-fill">{{ $itemLabel }}</span>
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
