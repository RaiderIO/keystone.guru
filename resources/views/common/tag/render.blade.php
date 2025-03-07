<?php

use App\Models\Tags\Tag;
use Illuminate\Support\Collection;

/**
 * @var Collection<Tag> $tags
 **/

$edit ??= false;
?>
@foreach($tags as $tag)
    <span
        class="tag badge badge-pill {{ is_null($tag->color) ? 'badge-primary' : '' }} {{ $edit ? 'edit' : '' }}"
        data-id="{{ $tag->id }}"
        style="{{ is_null($tagModel->color) ? '' : 'background-color: ' . $tagModel->color }}"
    >
        {{ $tag->name }}
        @if($edit)
            x
        @endif
    </span>
@endforeach
