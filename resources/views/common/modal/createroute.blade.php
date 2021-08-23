<ul class="nav nav-tabs" role="tablist">
    @auth
    <li class="nav-item">
        <a class="nav-link active" id="create_route_tab" data-toggle="tab" href="#create" role="tab"
           aria-controls="create_route" aria-selected="true">
            {{ __('views/common.modal.createroute.create_route') }}
        </a>
    </li>
    @endauth
    <li class="nav-item">
        <a class="nav-link {{ Auth::check() ? '' : 'active'}} " id="create_temporary_route_tab" data-toggle="tab" href="#create-temporary" role="tab"
           aria-controls="create_temporary_route_tab" aria-selected="false">
            {{ __('views/common.modal.createroute.create_temporary_route') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="create_route_import_mdt_tab" data-toggle="tab" href="#import" role="tab"
           aria-controls="create_route_import_mdt" aria-selected="false">
            {{ __('views/common.modal.createroute.import_from_mdt') }}
        </a>
    </li>
</ul>
<div class="tab-content">
    @auth
        <div id="create" class="tab-pane fade show active mt-3" role="tabpanel" aria-labelledby="create_route_tab">
            @include('common.forms.createroute')
        </div>
    @endauth
    <div id="create-temporary" class="tab-pane fade {{ Auth::check() ? '' : 'show active'}} mt-3" role="tabpanel"
         aria-labelledby="create_temporary_route_tab">
        @include('common.forms.createtemporaryroute')
    </div>
    <div id="import" class="tab-pane fade mt-3" role="tabpanel" aria-labelledby="create_route_import_mdt_tab">
        @include('common.forms.mdtimport')
    </div>
</div>
