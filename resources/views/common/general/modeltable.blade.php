<?php
/**
 * @var \Illuminate\Database\Eloquent\Model $model
 * @var                                     $exclude array
 */
$exclude ??= [];
?>
<table>
    <thead>
    <tr>
        <th class="p-1">
            Field
        </th>
        <th class="p-1">
            Value
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach($model->getAttributes() as $property => $value)
        @if(in_array($property, $exclude))
            @continue
        @endif
        <tr>
            <td class="p-1">{{ $property }}</td>
            <td class="p-1">{{ $value }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
