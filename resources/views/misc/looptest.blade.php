@extends('layouts.app')

@section('header-title', __('Loop test'))

@section('scripts')
    <script type="text/javascript">
        var p = [
            [-99.695, 93.03], [-100.76, 93.25], [-98.63, 92.81]
        ];

        $(function(){
            let offset = new Offset();
            console.log(p, c.map.killzone.arcSegments(p.length));
            let result = offset.data(p).arcSegments(9).margin(1);
            console.log(result);
        });
    </script>
@endsection

@section('content')
@endsection