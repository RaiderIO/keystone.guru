<?php
/**
 * @var string       $id
 * @var string|null  $name
 * @var array        $values
 * @var boolean|null $multiple
 * @var boolean|null $liveSearch
 */

$multiple = $multiple ?? false;
$liveSearch = $liveSearch ?? false;
?>
<select id="{{ $id }}"
        @isset($name)
            name="{{ $name }}"
        @endisset
        class="form-control selectpicker"
        @if($liveSearch)
        data-live-search="true"
        @endif
        @if($multiple)
            multiple
    @endif
>
    @foreach($values as $key => $value)
            <?php ob_start() ?>

        @include('common.forms.select.imageoption', [
            'url' => $value['icon_url'],
            'name' => $value['name'],
        ])

            <?php $html = ob_get_clean(); ?>
        <option value="{{ $key }}" data-content="{{{$html}}}">{{ $value['name'] }}</option>
    @endforeach
</select>
