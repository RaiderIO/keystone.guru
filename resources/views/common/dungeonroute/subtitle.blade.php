<?php
// Only add the 'clone of' when the user cloned it from someone else as a form of credit
if (isset($model->clone_of) && \App\Models\DungeonRoute::where('public_key', $model->clone_of)->where('author_id', $model->author_id)->count() === 0) {
    $subTitle = sprintf('%s %s', __('Clone of'),
        ' <a href="' . route('dungeonroute.view', ['dungeonroute' => $model->clone_of]) . '">' . $model->clone_of . '</a>'
    );
} else if( $model->demo ) {
    $subTitle = sprintf(__('Used with Dratnos\' permission'));
} else {
    $subTitle = sprintf(__('By %s'), $model->author->name);
}