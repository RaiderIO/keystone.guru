<?php
$options = isset($options) ? $options : [];
$section = isset($section) ? $section : true;

// Wrap in section tags of the inline code, otherwise just spit it out right now
if( $section ) { ?>
@section('scripts')
    @parent
    <script>
        document.addEventListener("DOMContentLoaded", function (event) {
            _inlineManager.activate('{{ $path }}', {!!  json_encode($options) !!} );
        });
    </script>
@endsection

<?php } else { ?>

<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        _inlineManager.activate('{{ $path }}', {!!  json_encode($options) !!} );
    });
</script>

<?php } ?>