<?php

/** @var string $category */
/** @var string $theme */

$tagCategoryNameMapping = [
    1 => __('views/common.tag.manager.route_personal'),
    2 => __('views/common.tag.manager.route_team')
];

$tags        = Auth::user()->tags(\App\Models\Tags\TagCategory::ALL[$category])->groupByRaw('name')->get()->groupBy(['tag_category_id']);
$isDarkTheme = $theme === 'darkly';
?>
@include('common.general.inline', ['path' => 'common/tag/tagmanager'])

@foreach($tags as $categoryId => $categoryTags)
    <div class="form-group">
        <h5>
            {{ $tagCategoryNameMapping[$categoryId] }}
        </h5>
        <div class="row">
            <div class="col-6 col-lg-3 font-weight-bold">
                {{ __('views/common.tag.manager.name') }}
            </div>
            <div class="col-4 col-lg-3 font-weight-bold">
                {{ __('views/common.tag.manager.color') }}
            </div>
            <div class="col-lg-4 d-none d-lg-block font-weight-bold">
                {{ __('views/common.tag.manager.usage') }}
            </div>
            <div class="col-2 col-lg-2 font-weight-bold">
                {{ __('views/common.tag.manager.actions') }}
            </div>
        </div>
        @foreach($categoryTags as $categoryTag)
            <div id="tag_row_{{ $categoryTag->id }}" class="row mt-1">
                <div class="col-6 col-lg-3">
                    {!! Form::text('tag_name', $categoryTag->name, ['id' => sprintf('tag_name_%d', $categoryTag->id), 'class' => 'form-control']) !!}
                </div>
                <div class="col-4 col-lg-3 ">
                    {!! Form::color('tag_color', $categoryTag->color ?? ($isDarkTheme ? '#375a7f' : '#ebebeb'), ['id' => sprintf('tag_color_%d', $categoryTag->id), 'class' => 'form-control']) !!}
                </div>
                <div class="col-lg-3 d-none d-lg-block">
                    {{ sprintf('%s %s(s)', $categoryTag->getUsage()->count(), strtolower($tagCategoryNameMapping[$categoryId])) }}
                </div>
                <div class="col-2 col-lg-3">
                    <div class="btn btn-primary tag_save" data-id="{{ $categoryTag->id }}">
                        <i class="fas fa-save"></i>
                        <span class="d-none d-xl-inline"> {{ __('views/common.tag.manager.save') }} </span>
                    </div>
                    <div class="btn btn-danger tag_delete" data-id="{{ $categoryTag->id }}">
                        <i class="fas fa-trash"></i>
                        <span class="d-none d-xl-inline"> {{ __('views/common.tag.manager.delete_all') }} </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach
{{ Form::model(Auth::user(), ['route' => $category === \App\Models\Tags\TagCategory::DUNGEON_ROUTE_PERSONAL ? 'profile.tag.create' : 'team.tag.create', 'method' => 'post']) }}
<div class="form-group{{ $errors->has('tag_name_new') ? ' has-error' : '' }}">
    {!! Form::label('tag_name_new', __('views/common.tag.manager.create_tag')) !!}
    {!! Form::text('tag_name_new', null, ['class' => 'form-control']) !!}
    @include('common.forms.form-error', ['key' => 'tag_name_new'])
</div>
{!! Form::submit(__('views/common.tag.manager.create_new_tag'), ['class' => 'btn btn-info']) !!}
{!! Form::close() !!}
