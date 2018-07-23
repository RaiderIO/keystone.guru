<?php
$affixGroups = \App\Models\AffixGroup::with(['affixes:affixes.id'])->get();
$affixes = \App\Models\Affix::with('iconfile')->get();
?>

@section('head')
    <style>
        .affix_row {
            width: 120px;
            padding-right: 10px;
        }

        .affix_list_row {
            padding-bottom: 10px;
        }

        .affixselect {
            height: 100px;
        }
    </style>
@endsection

@section('scripts')
    @parent

    <script>
        let _affixes = {!! $affixes !!};
        let _affixGroups = {!! $affixGroups !!};


        $(function () {
            let $affixesSelect = $('#affixes');
            $affixesSelect.bind('change', _refreshAffixesList);

            $.each(_affixGroups, function (index, group) {
                let affixes = _getAffixes(group);
                // console.log(affixes);

                let text = _getHtmlByAffixes(affixes, true);

                // console.log(text);

                $affixesSelect.append(jQuery('<option>', {
                    value: group.id,
                    text: 'text',
                    'data-content': text
                }));
            });

            // Refresh always; we removed options
            let $selectPicker = $('.selectpicker');
            $selectPicker.selectpicker('refresh');
            $selectPicker.selectpicker('render');
        });

        function _getHtmlByAffixes(affixes, select) {
            let html = '';
            for (let i = 0; i < affixes.length; i++) {
                let affix = affixes[i];
                html += $("#template_affix_dropdown_icon").html()
                    .replace(/{text}/g, affix.name)
                    .replace(/src=""/g, 'src="../images/' + affix.iconfile.path + '"')
            }
            return html;
        }

        function _refreshAffixesList() {
            let $el = $("#affixes_list");
            // Clear it completely
            $el.html('');
            // Get the current value
            let affixGroupIds = $(this).val();
            console.log(affixGroupIds);

            // Add the new affixes to the div
            $.each(affixGroupIds, function (index, affixGroupId) {
                affixGroupId = parseInt(affixGroupId);
                let affixGroup = _getAffixGroupById(affixGroupId);
                let affixes = _getAffixes(affixGroup);
                let $html = $("<div>").addClass('row col-lg-12 affix_list_row').html(
                    _getHtmlByAffixes(affixes, false)
                );
                $el.append($html);
            });
        }

        function _getAffixGroupById(id) {
            let result = null;
            $.each(_affixGroups, function (index, group) {
                console.log(index, group);
                if (group.id === id) {
                    result = group;
                    return false;
                }
            });

            return result;
        }

        /**
         * Finds all actual affix data from a list of IDs found in the group.
         * @param group
         * @returns {Array}
         */
        function _getAffixes(group) {
            let result = [];

            $.each(group.affixes, function (index, affix) {
                for (let i = 0; i < _affixes.length; i++) {
                    let affixCandidate = _affixes[i];
                    console.log(affixCandidate.id, '-', affix.id);
                    if (affixCandidate.id === affix.id) {
                        result.push(affixCandidate);
                        break;
                    }
                }
            });

            return result;
        }
    </script>

@endsection

<div class="col-lg-12">
    <div class="form-group">
        <select name="affixes[]" id="affixes" class="form-control selectpicker affixselect" multiple
                data-selected-text-format="count > 2">

        </select>

        <div id="affixes_list">

        </div>
    </div>
</div>

<div id="template_affix_dropdown_icon" style="display: none;">
    <div class="affix_row pull-left"><img src="" class="select_icon affix_icon" title="{text}"/> {text}</div>
</div>