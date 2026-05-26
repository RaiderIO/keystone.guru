<?php
/**
 * @var string       $id
 * @var string|null  $name
 * @var array        $values
 * @var boolean|null $multiple
 * @var boolean|null $liveSearch
 * @var mixed|null   $selected
 */

$multiple = $multiple ?? false;
$liveSearch = $liveSearch ?? false;
$selected = $selected ?? null;
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
        @if(!empty($value['icon_url']))
                <?php ob_start() ?>

            @include('common.forms.select.imageoption', [
                'url' => $value['icon_url'],
                'name' => $value['name'],
            ])

                <?php $html = ob_get_clean(); ?>
            <option value="{{ $key }}"
                    @if($selected !== null && $selected == $key) selected="selected" @endif
                    data-content="{{{$html}}}">{{ $value['name'] }}</option>
        @else
            <option value="{{ $key }}"
                    @if($selected !== null && $selected == $key) selected="selected" @endif
                    >{{ $value['name'] }}</option>
        @endif
    @endforeach
</select>
