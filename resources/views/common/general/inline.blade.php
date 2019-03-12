@section('scripts')
    @parent
    <script>
        document.addEventListener("DOMContentLoaded", function (event) {
            _inlineManager.activate('{{ $path }}');
        });
    </script>
@endsection