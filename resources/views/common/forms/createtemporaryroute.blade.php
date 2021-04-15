{{ Form::open(['route' => 'dungeonroute.temporary.savenew']) }}
<div class="container">
    @if( !isset($model) )
        @include('common.dungeon.select', ['id' => 'dungeon_id_select', 'showAll' => false])
    @endif

    <div class="form-group">
        <div class="text-info">
            @guest
                <i class="fas fa-info-circle"></i> {{ sprintf(
                    __('As an unregistered user, all created routes will be temporary routes which expire after %s hours.'),
                    config('keystoneguru.sandbox_dungeon_route_expires_hours')
                    )
                }}
            @else
                <i class="fas fa-info-circle"></i> {{
            sprintf(
                __('A temporary route will not show up in your profile and will be deleted automatically after %d hours unless it is claimed before that time.'),
                config('keystoneguru.sandbox_dungeon_route_expires_hours')
            )
                }}
            @endguest
        </div>
    </div>

    <div class="col-lg-12">
        <div class="form-group">
            {!! Form::submit(__('Create route'), ['class' => 'btn btn-info col-md-auto']) !!}
        </div>
    </div>
</div>

{!! Form::close() !!}