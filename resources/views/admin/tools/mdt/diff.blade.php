@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.tools.mdt.diff.title')])

@section('header-title', __('views/admin.tools.mdt.diff.header'))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('.apply_btn').bind('click', function () {
                var $this = $(this);
                console.log($this, $this.data('id'));


                $.ajax({
                    type: 'POST',
                    url: '/ajax/tools/mdt/diff/apply',
                    dataType: 'json',
                    data: {
                        category: $this.data('category'),
                        npc_id: $this.data('id'),
                        value: $this.data('new')
                    },
                    beforeSend: function () {
                        $this.addClass('btn-disabled');
                    },
                    success: function (json) {
                        // Remove the parent
                        $('#' + $this.data('category') + '_' + $this.data('id')).fadeOut(500);
                    },
                    complete: function () {
                        $this.removeClass('btn-disabled');
                    }
                });
            });
        });
    </script>

@endsection()

@section('content')
    <?php
    /** @var $warnings \Illuminate\Support\Collection */
    $warnings = $warnings->groupBy(function ($item)
    {
        /** @var $item \App\Logic\MDT\Exception\ImportWarning */
        return $item->getCategory();
    });

    $headers = [
        'mismatched_health'               => __('views/admin.tools.mdt.diff.headers.mismatched_health'),
        'mismatched_enemy_count'          => __('views/admin.tools.mdt.diff.headers.mismatched_enemy_count'),
        'mismatched_enemy_type'           => __('views/admin.tools.mdt.diff.headers.mismatched_enemy_type'),
        'missing_npc'                     => __('views/admin.tools.mdt.diff.headers.missing_npc'),
        'mismatched_enemy_forces'         => __('views/admin.tools.mdt.diff.headers.mismatched_enemy_forces'),
        'mismatched_enemy_forces_teeming' => __('views/admin.tools.mdt.diff.headers.mismatched_enemy_forces_teeming'),
    ]
    ?>

    @foreach($warnings as $key => $category)
        <div class="form-group">
            <h1>
                {{ $headers[$key] }}
            </h1>
            <table class="w-100">
                <tr>
                    <th width="15%">{{ __('views/admin.tools.mdt.diff.table_header_dungeon') }}</th>
                    <th width="20%">{{ __('views/admin.tools.mdt.diff.table_header_npc') }}</th>
                    <th width="30%">{{ __('views/admin.tools.mdt.diff.table_header_message') }}</th>
                    <th width="15%">{{ __('views/admin.tools.mdt.diff.table_header_actions') }}</th>
                </tr>
                @foreach($category as $importWarning)
                    <?php /** @var $importWarning \App\Logic\MDT\Exception\ImportWarning */
                    $data = ($importWarning->getData());
                    $mdtNpc = $data['mdt_npc'];

                    $old = $data['old'] ?? '';
                    $new = $data['new'] ?? '';
                    $count = isset($data['npc']) ? $data['npc']->enemies->count() : 0;
                    $dungeonName = isset($data['npc']) && isset($data['npc']->dungeon) ? $data['npc']->dungeon->name : __('');
                    ?>
                    <tr id="{{ $key . '_' . $mdtNpc->id }}">
                        <td>
                            {{ $dungeonName }}
                        </td>
                        <td>
                            {{
                            __('views/admin.tools.mdt.diff.npc_message', [
                                'npcName' => $mdtNpc->name ?? __('views/admin.tools.mdt.diff.no_npc_name_found'),
                                'npcId' => $mdtNpc->id,
                                'count' => $count]
                                )
                             }}
                        </td>
                        <td>
                            {{ $importWarning->getMessage() }}
                        </td>
                        <td>
                            @if( $key === 'mismatched_health' || $key === 'mismatched_enemy_forces' || $key === 'mismatched_enemy_forces_teeming' || $key === 'mismatched_enemy_type')
                                <button class="btn btn-primary apply_btn"
                                        data-id="{{ $mdtNpc->id }}" data-category="{{ $key }}"
                                        data-new="{{ $new }}">
                                    {{ __('views/admin.tools.mdt.diff.apply_mdt_kg') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
@endsection