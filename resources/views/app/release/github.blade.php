<?php /** @var $model \App\Models\Release */ ?>
@isset($model->changelog->description)
    {{ $model->changelog->description }}

@endisset
<?php // @formatter:off ?>
@foreach($model->changelog->changes->groupBy('release_changelog_category_id') as $categoryId => $changes)
{{ __(\App\Models\ReleaseChangelogCategory::findOrFail($categoryId)->name) }}:
@foreach($changes as $change)
  * @isset($change->ticket_id)#{{$change->ticket_id}}@endisset {!! $change->change !!}
@endforeach
<?php // @formatter:on ?>

@endforeach
