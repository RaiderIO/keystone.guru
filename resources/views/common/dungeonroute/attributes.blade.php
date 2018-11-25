<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
$label = isset($label) ? $label : __('Attributes');
?>

<div class="form-group">
    {!! Form::label('attributes', $label) !!}
    <?php
    /** @var \Illuminate\Support\Collection $attributes */
    $attributes = \App\Models\RouteAttribute::all()->groupBy('category');
    /** @var \Illuminate\Support\Collection $routeAttributes */
    $selectedIds = isset($selectedIds) ? $selectedIds : $dungeonroute->routeattributes->pluck('id');
    ?>
    <select multiple name="attributes" id="attributes" class="form-control selectpicker"
            size="{{ \App\Models\RouteAttribute::all()->count() + $attributes->count() }}">
        @foreach ($attributes as $category => $categoryAttributes)
            <optgroup label="{{ ucfirst($category) }}">
                @foreach ($categoryAttributes as $attribute) {
                <option value="{{ $attribute->id }}"
                        {{ in_array($attribute->id, $selectedIds) ? 'selected' : '' }}>
                    {{ $attribute->description }}
                </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>