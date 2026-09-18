<?php
/**
 * An ordered pick list: the chosen items as a numbered list that can be reordered by dragging or with
 * up/down buttons, plus a select to add more. The form receives `{$name}[]` in list order, which is
 * the only thing that makes the stored order match what the user arranged.
 *
 * @var string             $id           Prefix for every element id of this control.
 * @var string             $name         The array field name, posted as `{$name}[]`.
 * @var string             $label        Visible label of the control.
 * @var string             $labelClass   Classes of the label, e.g. to render it as a section heading.
 * @var array<int, string> $options      Every item that may be added, keyed by id, in the add select's order.
 * @var array<int, int>    $selectedIds  The ids currently in the list, in list order.
 * @var int                $max          Maximum number of items in the list.
 * @var string|null        $help         Help text below the control.
 * @var string             $emptyText    Shown in place of the list while it is empty.
 */
$help       ??= null;
$labelClass ??= 'form-label';
$errors     ??= collect();

$selectedIds = array_values(array_filter($selectedIds, static fn(int $selectedId): bool => isset($options[$selectedId])));
$isFull      = count($selectedIds) >= $max;
$helpId      = sprintf('%s_help', $id);
$errorKey    = $name;
?>
<div id="{{ $id }}" class="ordered_select">
    <div class="d-flex align-items-baseline">
        <label id="{{ $id }}_label" for="{{ $id }}_add" class="{{ $labelClass }}">
            {{ $label }}
        </label>
        <span id="{{ $id }}_count" class="ordered_select_count text-body-secondary ms-auto">
            {{ __('view_common.forms.orderedselect.count', ['count' => count($selectedIds), 'max' => $max]) }}
        </span>
    </div>

    <ol id="{{ $id }}_list" class="list-group ordered_select_list mb-2" aria-labelledby="{{ $id }}_label"
        @if(empty($selectedIds)) hidden @endif>
        @foreach($selectedIds as $index => $selectedId)
            @include('common.forms.orderedselectitem', [
                'name' => $name,
                'itemId' => $selectedId,
                'itemLabel' => $options[$selectedId],
                'position' => $index + 1,
            ])
        @endforeach
    </ol>

    <p id="{{ $id }}_empty" class="ordered_select_empty text-body-secondary mb-2" @if(!empty($selectedIds)) hidden @endif>
        {{ $emptyText }}
    </p>

    <div class="input-group">
        <select id="{{ $id }}_add" class="form-select{{ $errors->has($errorKey) ? ' is-invalid' : '' }}"
                aria-describedby="{{ $helpId }}" @disabled($isFull)>
            <option value="">{{ __('view_common.forms.orderedselect.choose') }}</option>
            @foreach($options as $optionId => $optionLabel)
                <option value="{{ $optionId }}" @disabled(in_array($optionId, $selectedIds, true))>{{ $optionLabel }}</option>
            @endforeach
        </select>
        <button id="{{ $id }}_add_button" type="button" class="btn btn-primary" @disabled($isFull)>
            <i class="fas fa-plus" aria-hidden="true"></i> {{ __('view_common.forms.orderedselect.add') }}
        </button>
    </div>

    <small id="{{ $helpId }}" class="form-text text-body-secondary d-block">
        <span id="{{ $id }}_full" @if(!$isFull) hidden @endif>
            {{ __('view_common.forms.orderedselect.full', ['max' => $max]) }}
        </span>
        @if($help !== null)
            {{ $help }}
        @endif
    </small>
    @include('common.forms.form-error', ['key' => $errorKey])

    <div id="{{ $id }}_status" class="visually-hidden" role="status" aria-live="polite"></div>

    <template id="{{ $id }}_template">
        @include('common.forms.orderedselectitem', [
            'name' => $name,
            'itemId' => 0,
            'itemLabel' => '',
            'position' => 0,
        ])
    </template>
</div>

@include('common.general.inline', ['path' => 'common/forms/orderedselect', 'options' => [
    'listSelector'      => sprintf('#%s_list', $id),
    'templateSelector'  => sprintf('#%s_template', $id),
    'addSelectSelector' => sprintf('#%s_add', $id),
    'addButtonSelector' => sprintf('#%s_add_button', $id),
    'emptySelector'     => sprintf('#%s_empty', $id),
    'countSelector'     => sprintf('#%s_count', $id),
    'fullSelector'      => sprintf('#%s_full', $id),
    'statusSelector'    => sprintf('#%s_status', $id),
    'max'               => $max,
    'countText'         => __('view_common.forms.orderedselect.count'),
    'moveUpText'        => __('view_common.forms.orderedselect.move_up'),
    'moveDownText'      => __('view_common.forms.orderedselect.move_down'),
    'removeText'        => __('view_common.forms.orderedselect.remove'),
    'addedStatusText'   => __('view_common.forms.orderedselect.added_status'),
    'movedStatusText'   => __('view_common.forms.orderedselect.moved_status'),
    'removedStatusText' => __('view_common.forms.orderedselect.removed_status'),
]])
