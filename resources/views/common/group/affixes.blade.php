<?php
$affixGroups = \App\Models\AffixGroup::with(['affixes:affixes.id'])->get();
$affixes = \App\Models\Affix::with('iconfile')->get();
?>

@section('head')
    <style>
        .affix_row {
            width: 100px;
            padding-right: 10px;
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
            $affixesSelect = $('#affixes');
            $.each(_affixGroups, function (index, group) {
                console.log(index, group);
                let affixes = getAffixes(group);
                // console.log(affixes);

                let text = '';
                for (let i = 0; i < affixes.length; i++) {
                    let affix = affixes[i];
                    text += $("#template_affix_dropdown_icon").html()
                        .replace(/{text}/g, affix.name)
                        .replace(/src=""/g, 'src="../images/' + affix.iconfile.path + '"')
                }

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

        /**
         * Finds all actual affix data from a list of IDs found in the group.
         * @param group
         * @returns {Array}
         */
        function getAffixes(group) {
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
        <select name="affixes[]" id="affixes" class="form-control selectpicker affixselect" multiple data-selected-text-format="count > 2">

        </select>
    </div>
</div>

<div id="template_affix_dropdown_icon" style="display: none;">
    <div class="affix_row pull-left"><img src="" class="select_icon affix_icon" title="{text}"/> {text}</div>
</div>