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
 * @var array<int|string, string> $options Every item that may be added, keyed by id, in the add select's order.
 * @var array<int, int|string> $selectedIds The ids currently in the list, in list order.
 * @var string|null        $formId       Form the hidden inputs belong to, for a control rendered outside of it.
 * @var int                $max          Maximum number of items in the list.
 * @var string|null        $help         Help text below the control.
 * @var string             $emptyText    Shown in place of the list while it is empty.
 * @var array<int, array{text: string, isWarning?: bool}> $optionDetails Optional secondary text per option id, shown
 *                                                                      next to its label; a warning is flagged.
 * @var string|null        $detailWarningText Read out (and shown on hover) for a flagged detail.
 * @var bool               $showCountMax Whether the counter reads "n / max" rather than just "n".
 * @var bool               $ajax         Changes are reported to the host page as events instead of being posted with a
 *                                       form; the add select makes way for a button the host page wires up.
 * @var string|null        $addLabel     Text of that button (ajax mode).
 * @var bool               $showAdd      Whether that button is shown (ajax mode).
 * @var bool               $showCount    Whether the "n / max" counter is shown.
 * @var int|null           $fullCount    What counts towards $max, when that is more than this list (ajax mode).
 * @var int|null           $itemMax      Maximum number of items in this list itself, when that is below $max (ajax mode).
 */
$help              ??= null;
$formId            ??= null;
$optionDetails     ??= [];
$detailWarningText ??= null;
$labelClass ??= 'form-label';
$errors     ??= collect();
$ajax       ??= false;
$addLabel   ??= __('view_common.forms.orderedselect.add');
$showCount  ??= true;
$showAdd    ??= true;
$showCountMax ??= true;

$selectedIds = array_values(array_filter($selectedIds, static fn(int|string $selectedId): bool => isset($options[$selectedId])));
$fullCount   ??= count($selectedIds);
$itemMax     ??= null;
$isListFull  = $fullCount >= $max;
$isItemsFull = $itemMax !== null && count($selectedIds) >= $itemMax;
$isFull      = $isListFull || $isItemsFull;
$helpId      = sprintf('%s_help', $id);
$errorKey    = $name;

$inlineOptions = [
    'listSelector'      => sprintf('#%s_list', $id),
    'templateSelector'  => sprintf('#%s_template', $id),
    'addSelectSelector' => sprintf('#%s_add', $id),
    'addButtonSelector' => sprintf('#%s_add_button', $id),
    'emptySelector'     => sprintf('#%s_empty', $id),
    'countSelector'     => sprintf('#%s_count', $id),
    'fullSelector'      => sprintf('#%s_full', $id),
    'statusSelector'    => sprintf('#%s_status', $id),
    'max'               => $max,
    'ajax'              => $ajax,
    'rootSelector'      => sprintf('#%s', $id),
    'fullCount'         => $ajax ? $fullCount : null,
    'itemMax'           => $itemMax,
    'showCountMax'      => $showCountMax,
];
?>
{{-- The data-inline-* attributes let a script activate this control again after swapping it into the page --}}
<div id="{{ $id }}" class="ordered_select" data-inline-id="{{ sprintf('%s_inline', $id) }}"
     data-inline-path="common/forms/orderedselect" data-inline-options="{{ json_encode($inlineOptions) }}">
    <div class="d-flex align-items-baseline">
        <label id="{{ $id }}_label" @if(!$ajax) for="{{ $id }}_add" @endif class="{{ $labelClass }}">
            {{ $label }}
        </label>
        @if($showCount)
            <span id="{{ $id }}_count" class="ordered_select_count text-body-secondary ms-auto">
                {{ $showCountMax ? __('js.orderedselect_count', ['count' => count($selectedIds), 'max' => $max]) : count($selectedIds) }}
            </span>
        @endif
    </div>

    <ol id="{{ $id }}_list" class="list-group ordered_select_list mb-2" aria-labelledby="{{ $id }}_label"
        @if(empty($selectedIds)) hidden @endif>
        @foreach($selectedIds as $index => $selectedId)
            @include('common.forms.orderedselectitem', [
                'name' => $name,
                'formId' => $formId,
                'itemId' => $selectedId,
                'itemLabel' => $options[$selectedId],
                'itemDetail' => $optionDetails[$selectedId] ?? null,
                'detailWarningText' => $detailWarningText,
                'position' => $index + 1,
            ])
        @endforeach
    </ol>

    <p id="{{ $id }}_empty" class="ordered_select_empty text-body-secondary mb-2" @if(!empty($selectedIds)) hidden @endif>
        {{ $emptyText }}
    </p>

    @if($ajax && $showAdd)
        <button id="{{ $id }}_add_button" type="button" class="btn btn-primary btn-sm ordered_select_add"
                aria-describedby="{{ $helpId }}" @disabled($isFull)>
            <i class="fas fa-plus" aria-hidden="true"></i> {{ $addLabel }}
        </button>
    @elseif(!$ajax)
        <div class="input-group">
            <select id="{{ $id }}_add" class="form-select{{ $errors->has($errorKey) ? ' is-invalid' : '' }}"
                    aria-describedby="{{ $helpId }}" @disabled($isFull)>
                <option value="">{{ __('view_common.forms.orderedselect.choose') }}</option>
                @foreach($options as $optionId => $optionLabel)
                    @php($optionDetail = $optionDetails[$optionId] ?? null)
                    <option value="{{ $optionId }}" @disabled(in_array($optionId, $selectedIds, true))
                        @if($optionDetail !== null) data-label="{{ $optionLabel }}" data-detail="{{ $optionDetail['text'] }}" data-detail-warning="{{ ($optionDetail['isWarning'] ?? false) ? 1 : 0 }}" @endif
                    >{{ $optionDetail !== null ? sprintf('%s (%s)', $optionLabel, $optionDetail['text']) : $optionLabel }}</option>
                @endforeach
            </select>
            <button id="{{ $id }}_add_button" type="button" class="btn btn-primary" @disabled($isFull)>
                <i class="fas fa-plus" aria-hidden="true"></i> {{ __('view_common.forms.orderedselect.add') }}
            </button>
        </div>
    @endif

    <small id="{{ $helpId }}" class="form-text text-body-secondary d-block">
        <span id="{{ $id }}_full" @if(!$isFull) hidden @endif>
            {{ __('js.orderedselect_full', ['max' => $isListFull ? $max : $itemMax]) }}
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
            'formId' => $formId,
            'itemId' => '',
            'itemLabel' => '',
            'itemDetail' => null,
            'detailWarningText' => $detailWarningText,
            'position' => 0,
        ])
    </template>
</div>

@include('common.general.inline', ['path' => 'common/forms/orderedselect', 'id' => sprintf('%s_inline', $id), 'options' => $inlineOptions])
