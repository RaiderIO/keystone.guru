<?php
$isMobile = (new \Jenssegers\Agent\Agent())->isMobile();
$selectedFloorId = isset($selectedFloorId) ? $selectedFloorId : 0;
$edit = isset($edit) ? $edit : false;
?>
<div id="{{ $id }}Toggle" class="sidebar-toggle anchor-{{$anchor}} {{ $isMobile ? '' : 'active' }}"
     data-toggle="tooltip">
    <i class="fas fa-arrow-{{ $isMobile ? 'right' : 'left' }}"></i>
</div>

<!-- Sidebar -->
<nav id="{{ $id }}" class="sidebar anchor-{{$anchor}} {{ $isMobile ? '' : 'active' }}">
    <div class="sidebar-header" style="background-image: url('/images/dungeons/{{$dungeon->expansion->shortname}}/{{$dungeon->key}}.jpg'); background-size: cover;">
        <h4 title="{!! $header !!}" data-toggle="tooltip">{!! $header !!}</h4>
        @isset($customSubHeader)
            {!! $customSubHeader !!}
        @else
            <div class="sidebar-header-subtitle">
                @isset($subHeader)
                    {!! $subHeader !!}
                @endisset
            </div>
            <div >
                <a class="sidebar-background" href="{{ route('home') }}"><i class="fas fa-arrow-{{ $anchor }}"></i> {{ __('Back to Keystone.guru') }}</a>
            </div>
        @endisset
    </div>
    @hasSection('sidebar-sticky')
        @yield('sidebar-sticky')
    @endif

    <div class="sidebar-content {{ $edit ? 'edit' : '' }}" data-simplebar
    @hasSection('sidebar-sticky')
        style="padding: 0 !important"
    @endif
    >
        <div class="container">
            {{ $slot }}
        </div>
    </div>
</nav>

@isset($show['sharing'])
    <!-- Modal mdt export -->
    @component('common.general.modal', ['id' => 'mdt_export_modal'])
        <div class='col-lg-12'>
            <h3>
                {{ __('MDT Export') }}
            </h3>
            <div class="form-group">
                <div class="mdt_export_loader_container">
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="mdt_export_result_container" style="display: none;">
                    <textarea id="mdt_export_result" class="w-100"  style="height: 400px" readonly></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="mdt_export_result_container" style="display: none;">
                    <div class="btn btn-info copy_mdt_string_to_clipboard w-100">
                        <i class="fas fa-copy"></i> {{ __('Copy to clipboard') }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="mdt_string_warnings">

                </div>
            </div>
        </div>
    @endcomponent
    <!-- END mdt export -->
@endisset