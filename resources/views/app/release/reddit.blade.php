<?php

use App\Models\Release;
use App\Models\ReleaseChangelogCategory;

/**
 * @var Release $model
 *
 **/
// @formatter:off
?>
[https://keystone.guru/release/{{ $model->version }}](https://keystone.guru/release/{{ $model->version }})
@isset($model->changelog->description)
    {{ $model->changelog->description }}
@endisset

@foreach($model->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $changes)
    {{ __(ReleaseChangelogCategory::findOrFail($categoryId)->name) }}:
    @foreach($changes as $change)
        * @isset($change->ticket_id)
            [\#{{$change->ticket_id}}](https://github.com/Wotuu/keystone.guru/issues/{{$change->ticket_id}})
        @endisset {!! $change->change !!}
    @endforeach

@endforeach
