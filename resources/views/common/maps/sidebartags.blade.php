<?php
/** @var \App\Models\Tags\TagModel[]|\Illuminate\Support\Collection $tagmodels */
$edit = isset($edit) ? $edit : false;
?>
@include('common.general.inline', ['path' => 'common/maps/sidebartags', 'options' => [
    'tags' => $tagmodels,
    'edit' => $edit,
]])
<div id="no_tags" class="row" style="display: {{ $tagmodels->isEmpty() ? 'block' : 'none' }}">
    <div class="col">
        {{ __('No tags assigned') }}
    </div>
</div>
<div class="row">
    <div id="tags_container" class="col">

    </div>
</div>
<div class="row mt-1">
    <div class="col">
        <div class="input-group">
            <label>
                <input id="new_tag_input" type="text" class="form-control" placeholder="{{ __('new tag') }}"/>
            </label>
            <div class="input-group-append">
                <div id="new_tag_color">

                </div>
            </div>
        </div>
    </div>
</div>
