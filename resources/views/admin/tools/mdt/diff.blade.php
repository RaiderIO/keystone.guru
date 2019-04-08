@extends('layouts.app', ['showAds' => false, 'title' => __('MDT Diff')])

@section('header-title', __('MDT Diff'))

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
    $warnings = $warnings->groupBy(function ($item) {
        /** @var $item \App\Logic\MDT\IO\ImportWarning */
        return $item->getCategory();
    });

    $headers = [
        'mismatched_health' => __('Mismatched health'),
        'mismatched_enemy_count' => __('Mismatched enemy count'),
        'missing_npc' => __('Missing NPC'),
        'mismatched_enemy_forces' => __('Mismatched enemy forces'),
    ]
    ?>

    @foreach($warnings as $key => $category)
        <div class="form-group">
            <h1>
                {{ $headers[$key] }}
            </h1>
            <table class="w-100">
                <tr>
                    <th width="25%">{{ __('NPC') }}</th>
                    <th width="60%">{{ __('Message') }}</th>
                    <th width="15%">{{ __('Actions') }}</th>
                </tr>
                @foreach($category as $importWarning)
                    <?php /** @var $importWarning \App\Logic\MDT\IO\ImportWarning */
                    $data = ($importWarning->getData());
                    $mdtNpc = $data['mdt_npc'];

                    $old = isset($data['old']) ? $data['old'] : '';
                    $new = isset($data['new']) ? $data['new'] : '';
                    $count = isset($data['npc']) ? $data['npc']->enemies->count() : 0;
                    ?>
                    <tr id="{{ $key . '_' . $mdtNpc->id }}">
                        <td>
                            {{ sprintf( '%s (%s, %s usages)', $mdtNpc->name, $mdtNpc->id, $count) }}
                        </td>
                        <td>
                            {{ $importWarning->getMessage() }}
                        </td>
                        <td>
                            @if( $key === 'mismatched_health' || $key === 'mismatched_enemy_forces')
                                <a class="btn btn-primary apply_btn"
                                   data-id="{{ $mdtNpc->id }}" data-category="{{ $key }}"
                                   data-new="{{ $new }}">
                                    {{ __('Apply (MDT -> KG)') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
@endsection