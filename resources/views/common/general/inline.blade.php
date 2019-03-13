<?php
$options = isset($options) ? $options : [];
?>
@section('scripts')
    @parent
    <script>
        document.addEventListener("DOMContentLoaded", function (event) {
            _inlineManager.activate('{{ $path }}', {!!  json_encode($options) !!} );
        });
    </script>
@endsection