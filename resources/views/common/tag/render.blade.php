<?php
/** @var \App\Models\Tags\TagModel[]|\Illuminate\Support\Collection $tagmodels */
$edit = isset($edit) ? $edit : false;
?>
@foreach($tagmodels as $tagModel)
    <span
        class="tag badge badge-pill {{ is_null($tagModel->color) ? 'badge-primary' : '' }} {{ $edit ? 'edit' : '' }}"
        data-id="{{ $tagModel->id }}"
        style="{{ is_null($tagModel->color) ? '' : 'background-color: ' . $tagModel->color }}"
    >
        {{ $tagModel->tag->name }}
        @if($edit)
            x
        @endif
    </span>
@endforeach