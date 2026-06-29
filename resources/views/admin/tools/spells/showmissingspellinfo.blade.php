<?php

use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Spell> $spells
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.spells.showmissingspellinfo.title')])

@section('header-title', __('view_admin.tools.spells.showmissingspellinfo.header'))

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#spells_table').DataTable({
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {

                })
            });
        });
    </script>
@endsection


@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.tools.spells.savetoseeder') }}" class="btn btn-primary">
            <i class="fas fa-download"></i> {{ __('view_admin.tools.spells.showmissingspellinfo.save_to_seeder') }}
        </a>
    </div>

    <div class="form-group">
        <table id="spells_table" class="tablesorter default_table table-striped">
            <thead>
            <tr>
                <th width="10%">{{ __('view_admin.tools.spells.showmissingspellinfo.table_header.id') }}</th>
                <th width="70%">{{ __('view_admin.tools.spells.showmissingspellinfo.table_header.name') }}</th>
                <th width="20%">{{ __('view_admin.tools.spells.showmissingspellinfo.table_header.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($spells as $spell)
                <tr>
                    <td>{{ $spell->id }}</td>
                    <td>{{ __($spell->name) }}</td>
                    <td>
                        <a href="{{ $spell->wowhead_url }}">
                            {{ __('view_admin.tools.spells.showmissingspellinfo.wowhead') }} <i
                                class="fa fa-external-link"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
