<?php /** @var $model \App\Models\Release */ ?>
@if($mention)
    @everyone
@endif
{{ $model->version }} ({{ $model->created_at->format('Y/m/d') }})(https://keystone.guru/release/{{ $model->version }})
@isset($model->description)
{{ $model->description }}

@endisset
@foreach($model->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $changes)
{{ \App\Models\ReleaseChangelogCategory::findOrFail($categoryId)->category }}:
@foreach($changes as $change)
    * @isset($change->ticket_id)#{{$change->ticket_id}}@endisset {!! $change->change !!}
@endforeach

@endforeach