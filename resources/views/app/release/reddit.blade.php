<?php /** @var $model \App\Models\Release */ ?>
[https://keystone.guru/release/{{ $model->version }}](https://keystone.guru/release/{{ $model->version }})
@isset($model->changelog->description)
    {{ $model->changelog->description }}
@endisset

@foreach($model->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $changes)
{{ \App\Models\ReleaseChangelogCategory::findOrFail($categoryId)->category }}:
@foreach($changes as $change)
* @isset($change->ticket_id)[\#{{$change->ticket_id}}](https://github.com/Wotuu/keystone.guru/issues/{{$change->ticket_id}})@endisset {!! $change->change !!}
@endforeach

@endforeach