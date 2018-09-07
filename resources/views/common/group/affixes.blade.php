<?php
$affixGroups = \App\Models\AffixGroup::with(['affixes:affixes.id,affixes.name'])->get();
$affixes = \App\Models\Affix::with('iconfile')->get();
?>

@section('head')
    <style>
        .affix_list {
            border: 1px solid #ccd0d2;
            border-radius: 4px;
        }

        .affix_row {
            padding-right: 10px;
        }

        .affix_list_row {
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .affix_list_row_selected {
            background-color: #F5F5F5;
        }

        .affix_list_row_disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .affix_list_row:hover {
            background-color: #ddd;
            cursor: pointer;
        }

        .affixselect {
            height: 100px;
        }
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        $(function () {
            $(".affix_list_row").bind('click', _affixRowClicked);

            // Perform loading of existing affix groups
            _applyAffixRowSelectionOnList();
        });

        function hasTeemingSelected(){

            if(teeming){
                $('.affix_row_no_teeming').addClass('affix_list_row_disabled');
            } else {

            }
        }

        function _affixRowClicked() {
            console.log(">> _affixRowClicked");
            let $el = $(this);
            // Convert to string since currentSelection has strings
            let id = $el.data('id') + "";

            // Affixes is leading!
            let $affixRowSelect = $("#affixes");
            let currentSelection = $affixRowSelect.val();

            // If it exists in the current selection
            let index = currentSelection.indexOf(id);
            if (index >= 0) {
                // remove it from the list
                currentSelection.splice(index, 1);
            }
            // Otherwise add it
            else {
                currentSelection.push(id);
            }

            $affixRowSelect.val(currentSelection);
            _applyAffixRowSelectionOnList();
            console.log("OK _affixRowClicked");
        }

        function _applyAffixRowSelectionOnList() {
            // console.log(">> _applyAffixRowSelectionOnList");

            let $list = $("#affixes_list_custom");
            let currentSelection = $("#affixes").val();

            $.each($list.children(), function (index, child) {
                let $child = $(child);
                let found = false;

                for (let i = 0; i < currentSelection.length; i++) {
                    if (parseInt(currentSelection[i]) === $child.data('id')) {
                        $child.addClass('affix_list_row_selected');
                        $child.find('.check').show();
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    $child.removeClass('affix_list_row_selected');
                    $child.find('.check').hide();
                }
            });
            // console.log("OK _applyAffixRowSelectionOnList");
        }
    </script>

@endsection

<div class="form-group">
    {!! Form::label('affixes[]', __('Select affixes') . "*") !!}
    {!! Form::select('affixes[]', \App\Models\AffixGroup::all()->pluck('id', 'id'),
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
            <div class="row affix_list_row {{ $affixGroup->isTeeming() ? 'affix_row_teeming' : 'affix_row_no_teeming' }}" data-id="{{ $affixGroup->id }}">
                @php( $count = 0 )
                @foreach($affixGroup->affixes as $affix)
                    @php( $number = count($affixGroup->affixes) - 1 === $count ? '3' : '4' )
                    <div class="col-xl-{{ $number }} col-lg-{{ $number }} col-md-{{ $number }} col-sm-{{ $number }} col-xs-{{ $number }} affix_row">
                        <img src="{{ Image::url($affix->iconfile->getUrl(), 32, 32) }}"
                             class="select_icon affix_icon"
                             data-toggle="tooltip"
                             title="{{ $affix->name }}"/>
                        <span class="hidden-xs-down"> {{ $affix->name }} </span>
                    </div>
                    @php( $count++ )
                @endforeach
                <span class="col-xl-1 col-lg-1 col-md-1 col-sm-1 col-xs-1 check text-right" style="display: none;">
                    <i class="fas fa-check"></i>
                </span>
            </div>
        @endforeach
    </div>
</div>