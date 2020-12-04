<?php
/** @var \App\Models\Tags\Tag[]|\Illuminate\Support\Collection $tags */
?>
@include('common.general.inline', ['path' => 'common/tag/edit', 'options' => [
    'tags' => $tags
]])
