<?php
/** This is the display of affixes when selecting them when creating a new route */

$affixGroups = \App\Models\AffixGroup::active()->with(['affixes:affixes.id,affixes.name,affixes.description'])->get();
$affixes = \App\Models\Affix::all();
?>

@include('common.general.inline', ['path' => 'common/group/affixes', 'options' => ['teemingSelector' => $teemingselector]])

<div class="form-group">
    {!! Form::label('affixes[]', __('Select affixes')) !!}
    {!! Form::select('affixes[]', \App\Models\AffixGroup::active()->get()->pluck('id', 'id'),
        !isset($dungeonroute) ? 0 : $dungeonroute->affixgroups->pluck(['affix_group_id']),
        ['id' => 'affixes', 'class' => 'form-control affixselect d-none', 'multiple'=>'multiple']) !!}
    {{--<select name="affixes[]" id="affixes" class="form-control affixselect hidden" multiple--}}
    {{--data-selected-text-format="count > 2">--}}
    {{--@foreach($affixGroups as $group)--}}
    {{--<option value="{{ $group->id }}">{{ $group->id }}</option>--}}
    {{--@endforeach--}}
    {{--</select>--}}

    <div id="affixes_list_custom" class="affix_list col-lg-12">
        @foreach($affixGroups as $affixGroup)
            <div class="row affix_list_row {{ $affixGroup->isTeeming() ? 'affix_row_teeming' : 'affix_row_no_teeming' }}"
                 {{ $affixGroup->isTeeming() ? 'style="display: none;"' : '' }}
                 data-id="{{ $affixGroup->id }}">
                @php( $count = 0 )
                @foreach($affixGroup->affixes as $affix)
                    @php( $number = count($affixGroup->affixes) - 1 === $count ? '2' : '3' )
                    <div class="col col-md-{{ $number }} affix_row">
                        <div class="row no-gutters">
                            <div class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->name) }}"
                                 data-toggle="tooltip"
                                 title="{{ $affix->description }}"
                                 style="height: 24px;">
                            </div>
                            <div class="col d-md-block d-none pl-1">
                                {{ $affix->name }}
                            </div>
                        </div>
                    </div>
                    @php( $count++ )
                @endforeach
                <span class="col col-md-1 text-right pl-0">
                    <span class="check" style="display: none;">
                        <i class="fas fa-check"></i>
                    </span>
                </span>
            </div>
        @endforeach
    </div>
</div>