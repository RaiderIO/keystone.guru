<?php /** @var $model \App\Models\Release */ ?>
@if($mention)
    @everyone
@endif
@isset($model->description)
{{ $model->description }}

@endisset
@foreach($model->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $changes)
**{{ \App\Models\ReleaseChangelogCategory::findOrFail($categoryId)->category }}**:
@foreach($changes as $change)
@isset($change->ticket_id)[#{{$change->ticket_id}}](https://github.com/wotuu/keystone.guru/issues/{{$change->ticket_id}})@endisset {!! $change->change !!}
@endforeach

@endforeach
[Home]({{$homeUrl}}) - [Changelog]({{$changelogUrl}}) - [Affixes]({{$affixesUrl}}) - [Patreon]({{$patreonUrl}}) - [Get started]({{$sandboxUrl}})