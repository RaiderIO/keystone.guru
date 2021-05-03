<?php
$options = $options ?? [];
$section = $section ?? true;
$modal = $modal ?? false;

// Wrap in section tags of the inline code, otherwise just spit it out right now
if( $section ) { ?>
@section('scripts')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function (event) {
            let code = _inlineManager.init('{{ $path }}', {!!  json_encode($options) !!} );

            if (!code.isActivated()) {
                <?php
                /** If modal is set, only load this when we're actually opening the modal to speed up loading. */
                if( $modal ){ ?>
                $('{{$modal}}').on('shown.bs.modal', function () {
                    <?php } ?>
                    _inlineManager.activate('{{ $path }}');
                    <?php if( $modal ){ ?>
                });
                <?php } ?>
            }
        });
    </script>
@endsection

<?php } else { ?>

<script>
    document.addEventListener('DOMContentLoaded', function (event) {
        let code = _inlineManager.init('{{ $path }}', {!!  json_encode($options) !!} );

        if (!code.isActivated()) {
            <?php
            /** If modal is set, only load this when we're actually opening the modal to speed up loading. */
            if( $modal ){ ?>
            $('{{$modal}}').on('shown.bs.modal', function () {
                <?php } ?>
                _inlineManager.activate('{{ $path }}');
                <?php if( $modal ){ ?>
            });
            <?php } ?>
        }
    });
</script>

<?php } ?>