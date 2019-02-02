<?php
/** This is the display of affixes when selecting them when creating a new route */

$affixGroups = \App\Models\AffixGroup::active()->with(['affixes:affixes.id,affixes.name,affixes.description'])->get();
$affixes = \App\Models\Affix::all();
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
            background-color: #2B3E50;
        }

        .affix_list_row_disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .affix_list_row:hover {
            background-color: #2B3E50;
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
        let _teemingSelector = '{{ $teemingselector }}';

        $(function () {
            $(_teemingSelector).bind('change', function () {
                _applyAffixRowSelectionOnList();
            });
            $(".affix_list_row").bind('click', _affixRowClicked);

            // Perform loading of existing affix groups
            _applyAffixRowSelectionOnList();
        });

        function _isTeemingSelected() {
            return $(_teemingSelector).is(':checked');
        }

        function _affixRowClicked() {
            // console.log(">> _affixRowClicked");
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
            // console.log("OK _affixRowClicked");
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

            if (_isTeemingSelected()) {
                $(".affix_row_no_teeming").hide();
                $(".affix_row_teeming").show();
            } else {
                $(".affix_row_no_teeming").show();
                $(".affix_row_teeming").hide();
            }
            // console.log("OK _applyAffixRowSelectionOnList");
        }
    </script>

@endsection

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