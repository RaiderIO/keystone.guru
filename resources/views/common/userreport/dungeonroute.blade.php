@section('scripts')
    @parent
    <script>
        $(function () {
            $('#featherlight_trigger').featherlight();
        });
    </script>
@endsection

@section('content')
    @parent
    <div style="display: none;">
        <div id="userreport_dungeonroute" class="card">
            {{ Form::open(['route' => 'userreport.new', 'autocomplete' => 'off']) }}
            <div class="card-header">
                <h4>
                    {{ __('Report dungeonroute') }}
                </h4>
            </div>
            <div class="card-body">
                {!! Form::hidden('userreport_context', $model->getReportContext(), ['class' => 'form-control']) !!}
                {!! Form::hidden('userreport_category', 'dungeonroute', ['class' => 'form-control']) !!}
                @guest
                    <div class="form-group">
                        {!! Form::label('userreport_username', __('Your name')) !!}
                        {!! Form::text('userreport_username', null, ['class' => 'form-control']) !!}
                    </div>
                @endguest
                <div class="form-group">
                    {!! Form::label('userreport_message', __('Why do you want to report this dungeonroute? (max. 1000 characters)')) !!}
                    {!! Form::textarea('userreport_message', null, ['class' => 'form-control', 'cols' => '50', 'rows' => '10']) !!}
                </div>
                {!! Form::submit(__('Submit'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
            </div>
            {!! Form::close() !!}
        </div>
    </div>

@endsection