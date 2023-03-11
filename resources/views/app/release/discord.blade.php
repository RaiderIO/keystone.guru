<?php /** @var $model \App\Models\Release */ ?>
@if($mention)
    @everyone
@endif
@isset($model->changelog->description)
    {{ $model->changelog->description }}

@endisset
@foreach($model->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $changes)
    **{{ __(\App\Models\ReleaseChangelogCategory::findOrFail($categoryId)->name) }}**:
    @foreach($changes as $change)
        @isset($change->ticket_id)[#{{$change->ticket_id}}](https://github.com/wotuu/keystone.guru/issues/{{$change->ticket_id}})@endisset {!! $change->change !!}
    @endforeach

@endforeach
